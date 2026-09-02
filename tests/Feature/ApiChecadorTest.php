<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiChecadorTest extends TestCase
{
    use RefreshDatabase;

    private array $centro;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centro = WorkCenter::create([
            'nombre' => 'Planta',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'radio_metros' => 150,
            'activo' => true,
        ])->toArray();

        $empleado = Employee::create([
            'numero_empleado' => 'EMP-1',
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'work_center_id' => $this->centro['id'],
            'activo' => true,
        ]);

        $this->user = User::create([
            'name' => 'Juan Pérez',
            'email' => 'empleado@test.test',
            'password' => 'password',
            'employee_id' => $empleado->id,
            'is_active' => true,
        ]);
    }

    public function test_login_exitoso_devuelve_token_y_perfil(): void
    {
        $resp = $this->postJson('/api/v1/auth/login', [
            'email' => 'empleado@test.test',
            'password' => 'password',
            'device' => ['uuid' => 'abc-123', 'marca' => 'Xiaomi', 'modelo' => 'Redmi'],
        ]);

        $resp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'user' => ['employee', 'work_center']]);
    }

    public function test_login_con_credenciales_incorrectas_falla(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'empleado@test.test',
            'password' => 'mala',
        ])->assertStatus(422);
    }

    public function test_checar_dentro_del_rango(): void
    {
        $token = $this->obtenerToken();

        $resp = $this->withToken($token)->postJson('/api/v1/checar', [
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'device' => ['uuid' => 'abc-123'],
        ]);

        $resp->assertOk()
            ->assertJsonPath('check_in.dentro_rango', true)
            ->assertJsonPath('check_in.tipo', 'entrada');

        $this->assertDatabaseHas('check_ins', [
            'tipo' => 'entrada',
            'dentro_rango' => true,
        ]);
    }

    public function test_checar_fuera_del_rango_se_registra_pero_marca_fuera(): void
    {
        $token = $this->obtenerToken();

        $resp = $this->withToken($token)->postJson('/api/v1/checar', [
            'tipo' => 'salida',
            'lat' => 20.670000,
            'lng' => -103.349609,
            'device' => ['uuid' => 'abc-123'],
        ]);

        $resp->assertOk()
            ->assertJsonPath('check_in.dentro_rango', false);

        $this->assertDatabaseHas('check_ins', [
            'tipo' => 'salida',
            'dentro_rango' => false,
        ]);
    }

    public function test_checar_sin_token_falla(): void
    {
        $this->postJson('/api/v1/checar', [
            'tipo' => 'entrada',
            'lat' => 20.0,
            'lng' => -103.0,
        ])->assertStatus(401);
    }

    public function test_estado_devuelve_perfil_y_ultima_checada(): void
    {
        $token = $this->obtenerToken();

        $this->withToken($token)->postJson('/api/v1/checar', [
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
        ])->assertOk();

        $resp = $this->withToken($token)->getJson('/api/v1/estado');

        $resp->assertOk()
            ->assertJsonPath('user.employee.numero_empleado', 'EMP-1')
            ->assertJsonPath('ultima_checada.tipo', 'entrada');
    }

    public function test_checar_con_biometrico_guarda_checkin_type_y_client_uuid(): void
    {
        $token = $this->obtenerToken();

        $resp = $this->withToken($token)->postJson('/api/v1/checar', [
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'checkin_type' => 'huella',
            'client_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'device' => ['uuid' => 'abc-123'],
        ]);

        $resp->assertOk()
            ->assertJsonPath('check_in.checkin_type', 'huella')
            ->assertJsonPath('check_in.sync_status', 'normal');

        $this->assertDatabaseHas('check_ins', [
            'client_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'checkin_type' => 'huella',
            'sync_status' => 'normal',
        ]);
    }

    public function test_checar_con_client_uuid_duplicado_devuelve_existente_sin_crear_nuevo(): void
    {
        $token = $this->obtenerToken();
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        // First request
        $this->withToken($token)->postJson('/api/v1/checar', [
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'checkin_type' => 'facial',
            'client_uuid' => $uuid,
        ])->assertOk();

        // Duplicate request
        $resp = $this->withToken($token)->postJson('/api/v1/checar', [
            'tipo' => 'salida', // different tipo
            'lat' => 20.659698,
            'lng' => -103.349609,
            'checkin_type' => 'huella',
            'client_uuid' => $uuid,
        ])->assertOk();

        $resp->assertJsonPath('registrada', false);

        // Only one checkin exists
        $this->assertEquals(1, CheckIn::byClientUuid($uuid)->count());
    }

    public function test_sync_pending_checar_crea_registro_con_sync_status_pendiente(): void
    {
        $token = $this->obtenerToken();

        $resp = $this->withToken($token)->postJson('/api/v1/checar/sync', [
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'checkin_type' => 'huella',
            'pending_checkin_datetime' => '2026-09-02T08:00:00Z',
            'client_uuid' => '550e8400-e29b-41d4-a716-446655440001',
            'device' => ['uuid' => 'abc-123'],
        ]);

        $resp->assertOk()
            ->assertJsonPath('check_in.sync_status', 'pendiente')
            ->assertJsonPath('check_in.checkin_type', 'huella');

        $this->assertDatabaseHas('check_ins', [
            'client_uuid' => '550e8400-e29b-41d4-a716-446655440001',
            'sync_status' => 'pendiente',
            'checkin_type' => 'huella',
        ]);
    }

    public function test_sync_pending_con_client_uuid_duplicado_devuelve_existente(): void
    {
        $token = $this->obtenerToken();
        $uuid = '550e8400-e29b-41d4-a716-446655440002';

        $this->withToken($token)->postJson('/api/v1/checar/sync', [
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'checkin_type' => 'huella',
            'pending_checkin_datetime' => '2026-09-02T08:00:00Z',
            'client_uuid' => $uuid,
        ])->assertOk();

        $resp = $this->withToken($token)->postJson('/api/v1/checar/sync', [
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'checkin_type' => 'huella',
            'pending_checkin_datetime' => '2026-09-02T08:00:00Z',
            'client_uuid' => $uuid,
        ])->assertOk();

        $resp->assertJsonPath('registrada', false);
        $this->assertEquals(1, CheckIn::byClientUuid($uuid)->count());
    }

    public function test_sync_pending_sin_auth_falla_con_401(): void
    {
        $this->postJson('/api/v1/checar/sync', [
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'checkin_type' => 'huella',
            'pending_checkin_datetime' => '2026-09-02T08:00:00Z',
            'client_uuid' => '550e8400-e29b-41d4-a716-446655440003',
        ])->assertStatus(401);
    }

    private function obtenerToken(): string
    {
        $resp = $this->postJson('/api/v1/auth/login', [
            'email' => 'empleado@test.test',
            'password' => 'password',
        ]);

        return $resp->json('token');
    }
}
