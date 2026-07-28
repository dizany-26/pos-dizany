<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckPermission;
use App\Models\Role;
use App\Models\User;
use App\Models\UsuarioPermiso;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CheckPermissionTest extends TestCase
{
    public function test_administrator_has_access_without_explicit_permission(): void
    {
        $user = $this->user(1, 'Administrador');

        $response = $this->runMiddleware($user, ['ventas']);

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_employee_has_access_with_matching_permission(): void
    {
        $user = $this->user(2, 'Empleado', ['ventas']);

        $response = $this->runMiddleware($user, ['ventas']);

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_employee_is_denied_without_matching_permission(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(0);

        $user = $this->user(2, 'Empleado', ['clientes']);

        $this->runMiddleware($user, ['ventas']);
    }

    private function user(int $roleId, string $roleName, array $permissions = []): User
    {
        $user = new User();
        $user->rol_id = $roleId;
        $user->setRelation('rol', new Role(['nombre' => $roleName]));
        $user->setRelation(
            'permisos',
            new Collection(array_map(
                fn (string $permission) => new UsuarioPermiso(['permiso' => $permission]),
                $permissions
            ))
        );

        return $user;
    }

    private function runMiddleware(User $user, array $permissions): Response
    {
        $request = Request::create('/prueba');
        $request->setUserResolver(fn () => $user);

        return (new CheckPermission())->handle(
            $request,
            fn () => new Response('', 204),
            ...$permissions
        );
    }
}
