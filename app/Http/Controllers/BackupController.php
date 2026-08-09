<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    private const DIRECTORY = 'app/backups/database';

    public function index()
    {
        $directory = storage_path(self::DIRECTORY);
        File::ensureDirectoryExists($directory);

        $backups = collect(File::files($directory))
            ->filter(fn ($file) => $file->getExtension() === 'sql')
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => $file->getMTime(),
                'restorable' => $this->isPortableBackup($file->getPathname()),
            ])
            ->sortByDesc('created_at')
            ->values();

        return view('configuracion.backups', compact('backups'));
    }

    public function store(): RedirectResponse
    {
        $connection = config('database.connections.mysql');
        abort_unless(($connection['driver'] ?? null) === 'mysql', 422, 'La copia automática requiere una conexión MySQL.');

        $executable = $this->findMysqlDump();
        if (! $executable) {
            return back()->withErrors('No se encontró mysqldump. Configura MYSQLDUMP_PATH en el archivo .env.');
        }

        $directory = storage_path(self::DIRECTORY);
        File::ensureDirectoryExists($directory);

        $filename = 'dizany_' . now()->format('Y-m-d_H-i-s') . '_' . Str::lower(Str::random(4)) . '.sql';
        $temporaryPath = $directory . DIRECTORY_SEPARATOR . $filename . '.tmp';
        $finalPath = $directory . DIRECTORY_SEPARATOR . $filename;
        $credentialsPath = $directory . DIRECTORY_SEPARATOR . '.mysql-' . Str::random(12) . '.cnf';

        try {
            $password = str_replace(
                ["\\", '"', "\n", "\r"],
                ["\\\\", '\\"', '\\n', '\\r'],
                (string) ($connection['password'] ?? '')
            );
            File::put($credentialsPath, "[client]\npassword=\"{$password}\"\n");

            $process = new Process([
                $executable,
                // MySQL exige que defaults-extra-file sea la primera opción.
                '--defaults-extra-file=' . $credentialsPath,
                '--host=' . ($connection['host'] ?? '127.0.0.1'),
                '--port=' . ($connection['port'] ?? '3306'),
                '--user=' . ($connection['username'] ?? 'root'),
                '--default-character-set=utf8mb4',
                '--single-transaction',
                '--quick',
                '--routines',
                '--triggers',
                '--events',
                '--hex-blob',
                '--result-file=' . $temporaryPath,
                (string) ($connection['database'] ?? ''),
            ], base_path());
            $process->setTimeout(300);
            $process->run();
        } catch (\Throwable $exception) {
            File::delete($temporaryPath);
            report($exception);

            return back()->withErrors('No se pudo iniciar la herramienta de respaldo. Revisa la configuración de mysqldump.');
        } finally {
            File::delete($credentialsPath);
        }

        if (! $process->isSuccessful() || ! File::exists($temporaryPath) || File::size($temporaryPath) === 0) {
            File::delete($temporaryPath);

            try {
                $this->createPortableDump($temporaryPath, (string) ($connection['database'] ?? ''));
            } catch (\Throwable $exception) {
                File::delete($temporaryPath);
                report(new \RuntimeException(
                    'Fallaron mysqldump y el respaldo alternativo. mysqldump: '
                    . $process->getErrorOutput(),
                    previous: $exception
                ));

                return back()->withErrors('No se pudo crear la copia. Revisa que MySQL esté iniciado e inténtalo nuevamente.');
            }
        }

        File::move($temporaryPath, $finalPath);

        return back()->with('success', 'Copia de seguridad creada correctamente.');
    }

    public function download(string $filename): BinaryFileResponse
    {
        $path = $this->resolveBackup($filename);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/sql',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(string $filename): RedirectResponse
    {
        File::delete($this->resolveBackup($filename));

        return back()->with('success', 'Copia de seguridad eliminada.');
    }

    public function restore(\Illuminate\Http\Request $request, string $filename): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:RESTAURAR'],
        ], [
            'password.current_password' => 'La contraseña del administrador no es correcta.',
            'confirmation.in' => 'Escribe RESTAURAR exactamente para confirmar.',
        ]);

        $path = $this->resolveBackup($filename);
        if (! $this->isPortableBackup($path)) {
            return back()->withErrors('Esta copia no es compatible con la restauración automática. Puedes descargarla para conservarla.');
        }

        $lock = Cache::lock('dizany-database-restore', 600);
        if (! $lock->get()) {
            return back()->withErrors('Ya hay una restauración en proceso. Espera unos minutos.');
        }

        $directory = storage_path(self::DIRECTORY);
        $emergencyName = 'dizany_' . now()->format('Y-m-d_H-i-s') . '_' . Str::lower(Str::random(4)) . '.sql';
        $emergencyPath = $directory . DIRECTORY_SEPARATOR . $emergencyName;
        $restored = false;

        try {
            // Siempre conservar el estado inmediatamente anterior.
            $this->createPortableDump($emergencyPath, (string) config('database.connections.mysql.database'));
            Artisan::call('down', ['--retry' => 60]);

            try {
                $this->restorePortableDump($path);
                $restored = true;
            } catch (\Throwable $restoreError) {
                // Si algo falla, regresar automáticamente al estado de emergencia.
                $this->restorePortableDump($emergencyPath);
                throw $restoreError;
            }

            $this->writeRestoreAudit('success', $filename, $emergencyName, $request);
        } catch (\Throwable $exception) {
            $this->writeRestoreAudit('failed', $filename, $emergencyName, $request, $exception->getMessage());
            report($exception);

            return back()->withErrors('La restauración no pudo completarse. La base anterior fue protegida con una copia de emergencia.');
        } finally {
            Artisan::call('up');
            $lock->release();
        }

        if ($restored) {
            $this->closeAllSessions($request);
        }

        return redirect()->route('login', ['restored' => 1]);
    }

    private function resolveBackup(string $filename): string
    {
        abort_unless(
            preg_match('/^dizany_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_[a-z0-9]{4}\.sql$/', $filename),
            404
        );

        $path = storage_path(self::DIRECTORY . DIRECTORY_SEPARATOR . $filename);
        abort_unless(File::isFile($path), 404);

        return $path;
    }

    private function isPortableBackup(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return false;
        }

        $header = fread($handle, 80);
        fclose($handle);

        return str_starts_with((string) $header, '-- Copia de seguridad DIZANY');
    }

    private function findMysqlDump(): ?string
    {
        $candidates = array_filter([
            config('backups.mysqldump_path'),
            PHP_OS_FAMILY === 'Windows' ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe' : null,
            PHP_OS_FAMILY === 'Windows' ? 'mysqldump.exe' : 'mysqldump',
        ]);

        foreach ($candidates as $candidate) {
            if (str_contains($candidate, DIRECTORY_SEPARATOR) && File::isFile($candidate)) {
                return $candidate;
            }

            if (! str_contains($candidate, DIRECTORY_SEPARATOR)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Genera un SQL restaurable usando la conexión que Laravel ya tiene abierta.
     * Es el respaldo alternativo para entornos Windows donde proc_open no puede
     * abrir una segunda conexión TCP desde el servidor web.
     */
    private function createPortableDump(string $path, string $database): void
    {
        $pdo = DB::connection('mysql')->getPdo();
        $handle = fopen($path, 'wb');

        if (! $handle) {
            throw new \RuntimeException('No se pudo crear el archivo temporal del respaldo.');
        }

        try {
            fwrite($handle, "-- Copia de seguridad DIZANY\n");
            fwrite($handle, '-- Generada: ' . now()->format('Y-m-d H:i:s') . "\n");
            fwrite($handle, '-- Base de datos: ' . str_replace(["\r", "\n"], '', $database) . "\n\n");
            fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

            $objects = $pdo->query('SHOW FULL TABLES')->fetchAll(\PDO::FETCH_NUM);
            $tables = array_values(array_filter($objects, fn ($row) => ($row[1] ?? '') === 'BASE TABLE'));
            $views = array_values(array_filter($objects, fn ($row) => ($row[1] ?? '') === 'VIEW'));

            foreach ($tables as $row) {
                $table = (string) $row[0];
                $identifier = $this->quoteIdentifier($table);
                $createRow = $pdo->query("SHOW CREATE TABLE {$identifier}")->fetch(\PDO::FETCH_NUM);

                fwrite($handle, "-- Estructura: {$table}\nDROP TABLE IF EXISTS {$identifier};\n");
                fwrite($handle, ($createRow[1] ?? '') . ";\n\n");

                $statement = $pdo->query("SELECT * FROM {$identifier}");
                $columns = null;
                $batch = [];

                while ($record = $statement->fetch(\PDO::FETCH_ASSOC)) {
                    $columns ??= array_keys($record);
                    $values = array_map(
                        fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                        array_values($record)
                    );
                    $batch[] = '(' . implode(', ', $values) . ')';

                    if (count($batch) === 100) {
                        $this->writeInsertBatch($handle, $identifier, $columns, $batch);
                        $batch = [];
                    }
                }

                if ($batch !== []) {
                    $this->writeInsertBatch($handle, $identifier, $columns ?? [], $batch);
                }

                fwrite($handle, "\n");
            }

            foreach ($views as $row) {
                $view = (string) $row[0];
                $identifier = $this->quoteIdentifier($view);
                $createRow = $pdo->query("SHOW CREATE VIEW {$identifier}")->fetch(\PDO::FETCH_ASSOC);
                $createSql = $createRow['Create View'] ?? array_values($createRow)[1] ?? null;

                if ($createSql) {
                    fwrite($handle, "DROP VIEW IF EXISTS {$identifier};\n{$createSql};\n\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }

        if (! File::exists($path) || File::size($path) === 0) {
            throw new \RuntimeException('El respaldo alternativo se generó vacío.');
        }
    }

    private function writeInsertBatch($handle, string $table, array $columns, array $rows): void
    {
        if ($columns === [] || $rows === []) {
            return;
        }

        $columnList = implode(', ', array_map([$this, 'quoteIdentifier'], $columns));
        fwrite($handle, "INSERT INTO {$table} ({$columnList}) VALUES\n" . implode(",\n", $rows) . ";\n");
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function restorePortableDump(string $path): void
    {
        abort_unless($this->isPortableBackup($path), 422, 'La copia no pertenece a DIZANY.');

        $pdo = DB::connection('mysql')->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Eliminar primero las vistas y después las tablas para reproducir
            // exactamente el estado guardado, sin dejar estructuras posteriores.
            $objects = $pdo->query('SHOW FULL TABLES')->fetchAll(\PDO::FETCH_NUM);
            foreach ($objects as $object) {
                if (($object[1] ?? '') === 'VIEW') {
                    $pdo->exec('DROP VIEW IF EXISTS ' . $this->quoteIdentifier((string) $object[0]));
                }
            }
            foreach ($objects as $object) {
                if (($object[1] ?? '') === 'BASE TABLE') {
                    $pdo->exec('DROP TABLE IF EXISTS ' . $this->quoteIdentifier((string) $object[0]));
                }
            }

            $sql = File::get($path);
            foreach ($this->splitSqlStatements($sql) as $statement) {
                $pdo->exec($statement);
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            DB::purge('mysql');
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $escaped = false;
        $length = strlen($sql);

        for ($index = 0; $index < $length; $index++) {
            $character = $sql[$index];
            $next = $index + 1 < $length ? $sql[$index + 1] : '';

            if ($quote !== null) {
                $buffer .= $character;
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($character === "'" || $character === '"' || $character === '`') {
                $quote = $character;
                $buffer .= $character;
                continue;
            }

            if ($character === '-' && $next === '-' && ($index + 2 >= $length || ctype_space($sql[$index + 2]))) {
                while ($index < $length && $sql[$index] !== "\n") {
                    $index++;
                }
                $buffer .= "\n";
                continue;
            }

            if ($character === '#') {
                while ($index < $length && $sql[$index] !== "\n") {
                    $index++;
                }
                $buffer .= "\n";
                continue;
            }

            if ($character === '/' && $next === '*') {
                $index += 2;
                while ($index + 1 < $length && ! ($sql[$index] === '*' && $sql[$index + 1] === '/')) {
                    $index++;
                }
                $index++;
                continue;
            }

            if ($character === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $character;
        }

        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }

        return $statements;
    }

    private function closeAllSessions(\Illuminate\Http\Request $request): void
    {
        Auth::logout();

        if (config('session.driver') === 'database') {
            try {
                DB::table(config('session.table', 'sessions'))->delete();
            } catch (\Throwable $exception) {
                report($exception);
            }
        } elseif (config('session.driver') === 'file') {
            File::cleanDirectory(storage_path('framework/sessions'));
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function writeRestoreAudit(
        string $status,
        string $filename,
        string $emergencyName,
        \Illuminate\Http\Request $request,
        ?string $error = null
    ): void {
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/backup-restores.log'),
        ])->info('Restauración de base de datos', [
            'status' => $status,
            'backup' => $filename,
            'emergency_backup' => $emergencyName,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->nombre,
            'ip' => $request->ip(),
            'error' => $error,
        ]);
    }
}
