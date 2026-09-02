<?php

namespace Tests\Feature;

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

    private function obtenerToken(): string
    {
        $resp = $this->postJson('/api/v1/auth/login', [
            'email' => 'empleado@test.test',
            'password' => 'password',
        ]);

        return $resp->json('token');
    }
}
