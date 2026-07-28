<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
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
            'lotes.ajustar' => ['auth', 'role:Administrador'],
            'ventas.destroy' => ['auth', 'role:Administrador'],
            'gastos.destroy' => ['auth', 'role:Administrador'],
            'catalogo.admin.config.update' => ['auth', 'permission:catalogo.config'],
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
        foreach (['login', 'password.request', 'password.reset', 'catalogo'] as $routeName) {
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

    private function routeByName(string $name): Route
    {
        $route = $this->app['router']->getRoutes()->getByName($name);

        $this->assertNotNull($route, "No existe la ruta [{$name}].");

        return $route;
    }
}
