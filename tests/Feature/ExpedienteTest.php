<?php

namespace Tests\Feature;

use App\Livewire\GenerateExpediente;
use App\Models\CheckIn;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExpedienteTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConAuditoria(): User
    {
        Permission::firstOrCreate(['name' => 'ver_auditoria']);

        $user = User::create([
            'name' => 'Auditor',
            'email' => 'auditor@test.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->givePermissionTo('ver_auditoria');

        return $user;
    }

    private function empleado(?WorkCenter $centro = null): Employee
    {
        $centro ??= WorkCenter::create([
            'nombre' => 'Planta',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'radio_metros' => 150,
            'activo' => true,
        ]);

        return Employee::create([
            'numero_empleado' => 'EMP-1',
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'work_center_id' => $centro->id,
            'activo' => true,
        ]);
    }

    public function test_pdf_route_returns_inline_pdf_for_authorized_user(): void
    {
        $user = $this->usuarioConAuditoria();
        $empleado = $this->empleado();

        CheckIn::create([
            'employee_id' => $empleado->id,
            'user_id' => $user->id,
            'work_center_id' => $empleado->work_center_id,
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'dentro_rango' => true,
            'fecha_dispositivo' => '2026-09-10 08:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('admin.expediente.pdf', [
            'employee_id' => $empleado->id,
            'from' => '2026-09-01',
            'to' => '2026-09-30',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_route_is_forbidden_without_permission(): void
    {
        $empleado = $this->empleado();

        $user = User::create([
            'name' => 'Sin permiso',
            'email' => 'sinpermiso@test.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.expediente.pdf', [
                'employee_id' => $empleado->id,
                'from' => '2026-09-01',
                'to' => '2026-09-30',
            ]))
            ->assertForbidden();
    }

    public function test_search_is_gated_until_minimum_length(): void
    {
        $empleado = $this->empleado();

        $corto = Livewire::test(GenerateExpediente::class)->set('employeeSearch', 'Ju');
        $this->assertTrue($corto->viewData('employeeResults')->isEmpty());

        $largo = Livewire::test(GenerateExpediente::class)->set('employeeSearch', 'Juan');
        $this->assertTrue($largo->viewData('employeeResults')->contains('id', $empleado->id));
    }

    public function test_generate_fails_when_end_date_is_before_start_date(): void
    {
        $empleado = $this->empleado();

        Livewire::test(GenerateExpediente::class)
            ->set('employee_id', $empleado->id)
            ->set('start_date', '2026-09-10')
            ->set('end_date', '2026-09-01')
            ->call('generate')
            ->assertHasErrors('end_date');
    }

    public function test_generate_stores_report_with_pdf_url_and_date_range(): void
    {
        $user = $this->usuarioConAuditoria();
        $empleado = $this->empleado();

        CheckIn::create([
            'employee_id' => $empleado->id,
            'user_id' => $user->id,
            'work_center_id' => $empleado->work_center_id,
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'dentro_rango' => true,
            'fecha_dispositivo' => '2026-09-10 08:00:00',
        ]);
        CheckIn::create([
            'employee_id' => $empleado->id,
            'user_id' => $user->id,
            'work_center_id' => $empleado->work_center_id,
            'tipo' => 'salida',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'dentro_rango' => true,
            'fecha_dispositivo' => '2026-09-10 16:00:00',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(GenerateExpediente::class)
            ->set('employee_id', $empleado->id)
            ->set('start_date', '2026-09-01')
            ->set('end_date', '2026-09-30')
            ->call('generate')
            ->assertHasNoErrors();

        $report = $component->get('report');

        $this->assertSame('01/09/2026 — 30/09/2026', $report['period']);
        $this->assertSame('2026-09-01', $report['start_date']);
        $this->assertSame('2026-09-30', $report['end_date']);
        $this->assertSame(2, $report['total']);
        $this->assertStringContainsString(
            route('admin.expediente.pdf', [
                'employee_id' => $empleado->id,
                'from' => '2026-09-01',
                'to' => '2026-09-30',
            ]),
            $report['pdf_url'],
        );
    }
}
