<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use ReflectionClass;
use Tests\TestCase;

class RouteSecurityTest extends TestCase
{
    public function test_sensitive_routes_require_authentication_and_authorization(): void
    {
        $expectedMiddleware = [
            'usuarios.store' => ['auth', 'permission:usuarios'],
            'productos.update' => ['auth', 'permission:productos'],
            'inventario.lote.store' => ['auth', 'permission:inventario.lote'],
            'inventario.compras' => ['auth', 'permission:inventario.lote'],
            'inventario.compras.pagos' => ['auth', 'permission:inventario.lote', 'role:Administrador'],
            'inventario.compra-en-curso.limpiar' => ['auth', 'permission:inventario.lote'],
            'lotes.ajustar' => ['auth', 'role:Administrador'],
            'gastos.destroy' => ['auth', 'role:Administrador'],
            'movimientos.compras.pagos' => ['auth', 'permission:movimientos', 'role:Administrador'],
            'catalogo.admin.config.update' => ['auth', 'permission:catalogo.config'],
            'backups.index' => ['auth', 'permission:backups'],
            'backups.store' => ['auth', 'permission:backups'],
            'backups.download' => ['auth', 'permission:backups'],
            'backups.restore' => ['auth', 'permission:backups'],
            'backups.destroy' => ['auth', 'permission:backups'],
        ];

        foreach ($expectedMiddleware as $routeName => $middleware) {
            $route = $this->routeByName($routeName);
            $applied = $route->gatherMiddleware();

            foreach ($middleware as $expected) {
                $this->assertContains(
                    $expected,
                    $applied,
                    "La ruta [{$routeName}] no contiene el middleware [{$expected}]."
                );
            }
        }
    }

    public function test_public_routes_do_not_require_authentication(): void
    {
        foreach (['login', 'password.request', 'password.reset', 'password.reset.legacy', 'catalogo'] as $routeName) {
            $this->assertNotContains('auth', $this->routeByName($routeName)->gatherMiddleware());
        }
    }

    public function test_sale_registration_is_not_excluded_from_csrf_protection(): void
    {
        $reflection = new ReflectionClass(VerifyCsrfToken::class);
        $property = $reflection->getProperty('except');
        $middleware = new VerifyCsrfToken($this->app, $this->app['encrypter']);

        $this->assertNotContains(
            '/ventas/registrar',
            $property->getValue($middleware)
        );
    }

    public function test_ngrok_forwarded_origin_is_recognized_from_local_proxy(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'localhost:8000',
            'HTTP_X_FORWARDED_HOST' => 'hauriant-irrelatively-emely.ngrok-free.dev',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        ]);

        $response = app(TrustProxies::class)->handle($request, static function (Request $trustedRequest) {
            return response()->json([
                'origin' => $trustedRequest->getSchemeAndHttpHost(),
            ]);
        });

        $this->assertSame(
            'https://hauriant-irrelatively-emely.ngrok-free.dev',
            $response->getData(true)['origin']
        );
    }

    private function routeByName(string $name): Route
    {
        $route = $this->app['router']->getRoutes()->getByName($name);

        $this->assertNotNull($route, "No existe la ruta [{$name}].");

        return $route;
    }
}
