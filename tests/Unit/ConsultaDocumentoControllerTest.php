<?php

namespace Tests\Unit;

use App\Http\Controllers\ConsultaDocumentoController;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsultaDocumentoControllerTest extends TestCase
{
    public function test_it_normalizes_a_ruc_response(): void
    {
        config(['services.apiperu.token' => 'test-token']);
        Http::fake([
            'apiperu.dev/api/ruc/*' => Http::response([
                'success' => true,
                'data' => [
                    'nombre_o_razon_social' => 'PROVEEDOR PRUEBA SAC',
                    'direccion' => 'AV. PRUEBA 123',
                    'estado' => 'ACTIVO',
                    'condicion' => 'HABIDO',
                ],
            ]),
        ]);

        $response = app(ConsultaDocumentoController::class)->show('ruc', '20123456789');

        $this->assertSame(200, $response->status());
        $this->assertSame('PROVEEDOR PRUEBA SAC', $response->getData(true)['nombre']);
        $this->assertSame('AV. PRUEBA 123', $response->getData(true)['direccion']);
    }

    public function test_it_normalizes_a_dni_response(): void
    {
        config(['services.apiperu.token' => 'test-token']);
        Http::fake([
            'apiperu.dev/api/dni/*' => Http::response([
                'success' => true,
                'data' => [
                    'nombres' => 'ANA',
                    'apellido_paterno' => 'PÉREZ',
                    'apellido_materno' => 'DÍAZ',
                ],
            ]),
        ]);

        $response = app(ConsultaDocumentoController::class)->show('dni', '12345678');

        $this->assertSame(200, $response->status());
        $this->assertSame('ANA PÉREZ DÍAZ', $response->getData(true)['nombre']);
        $this->assertNull($response->getData(true)['direccion']);
    }
}
