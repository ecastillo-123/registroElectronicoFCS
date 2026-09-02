<?php

namespace Tests\Feature;

use App\Filament\Resources\CheckIns\CheckInResource;
use App\Filament\Resources\CheckIns\Pages\ListCheckIns;
use App\Filament\Resources\CompanyResource\Pages\CreateCompany;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\WorkCenterResource\Pages\CreateWorkCenter;
use App\Models\CheckIn;
use App\Models\Company;
use App\Models\Device;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkCenter;
use App\Services\CheckInExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdmin(): User
    {
        $permisos = [
            'ver_checadas',
            'validar_checadas',
            'gestionar_centros',
            'gestionar_empresas',
            'gestionar_empleados',
            'gestionar_usuarios',
            'gestionar_roles',
        ];
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permisos);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('admin');

        return $user;
    }

    private function datosDemo(User $admin): array
    {
        $centro = WorkCenter::create([
            'nombre' => 'Planta',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'radio_metros' => 150,
            'activo' => true,
        ]);
        $empleado = Employee::create([
            'numero_empleado' => 'EMP-1',
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'work_center_id' => $centro->id,
            'activo' => true,
        ]);
        $admin->update(['employee_id' => $empleado->id]);

        return [$centro, $empleado];
    }

    public function test_login_page_carga(): void
    {
        $this->get('/admin/login')->assertStatus(200);
    }

    public function test_paginas_admin_cargan_para_admin(): void
    {
        $admin = $this->crearAdmin();
        $this->datosDemo($admin);

        $this->actingAs($admin);

        $this->get('/admin')->assertOk();
        $this->get('/admin/companies')->assertOk();
        $this->get('/admin/work-centers')->assertOk();
        $this->get('/admin/work-centers/create')->assertOk();
        $this->get('/admin/employees')->assertOk();
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/roles')->assertOk();
        $this->get('/admin/check-ins')->assertOk();
    }

    public function test_usuario_sin_permisos_no_ve_recursos(): void
    {
        $admin = $this->crearAdmin();
        $this->datosDemo($admin);

        $user = User::create([
            'name' => 'Revisor',
            'email' => 'revisor@test.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $this->get('/admin/work-centers')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_aprobacion_de_checada(): void
    {
        $admin = $this->crearAdmin();
        [, $empleado] = $this->datosDemo($admin);

        $checada = CheckIn::create([
            'employee_id' => $empleado->id,
            'user_id' => $admin->id,
            'work_center_id' => 1,
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'dentro_rango' => false,
            'distancia_metros' => 500,
        ]);

        $this->assertNull($checada->validado);

        $checada->update([
            'validado' => true,
            'validado_por' => $admin->id,
            'validado_at' => now(),
        ]);

        $this->assertTrue($checada->fresh()->validado);
    }

    public function test_crear_centro_normaliza_coordenadas_sin_punto_decimal(): void
    {
        $admin = $this->crearAdmin();
        $this->actingAs($admin);

        $empresa = Company::create([
            'nombre' => 'Empresa Principal',
        ]);

        Livewire::test(CreateWorkCenter::class)
            ->fillForm([
                'company_id' => $empresa->id,
                'nombre' => 'Casa',
                'direccion' => 'Santa Elena #226, Jiutepec',
                'radio_metros' => 100,
                'activo' => true,
                'lat' => '18.9224536',
                'lng' => '-991815521',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('work_centers', [
            'nombre' => 'Casa',
            'company_id' => $empresa->id,
            'lat' => 18.9224536,
            'lng' => -99.1815521,
        ]);
    }

    public function test_crear_usuario_toma_correo_del_empleado(): void
    {
        $admin = $this->crearAdmin();
        [, $empleado] = $this->datosDemo($admin);
        $empleado->update(['email' => 'juan.perez@checador.test']);

        $rol = Role::firstOrCreate(['name' => 'consulta']);

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Juan Pérez',
                'employee_id' => $empleado->id,
                'roles' => [$rol->id],
                'password' => 'secret123',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'juan.perez@checador.test',
            'employee_id' => $empleado->id,
        ]);
    }

    public function test_tabla_checadas_muestra_texto_compacto(): void
    {
        $admin = $this->crearAdmin();
        [, $empleado] = $this->datosDemo($admin);

        CheckIn::create([
            'employee_id' => $empleado->id,
            'user_id' => $admin->id,
            'work_center_id' => 1,
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'dentro_rango' => true,
            'distancia_metros' => 12.5,
        ]);

        $this->actingAs($admin);

        $this->get('/admin/check-ins')
            ->assertOk()
            ->assertSee('text-xs');
    }

    public function test_modal_checada_muestra_mapa(): void
    {
        $admin = $this->crearAdmin();
        [, $empleado] = $this->datosDemo($admin);

        $checada = CheckIn::create([
            'employee_id' => $empleado->id,
            'user_id' => $admin->id,
            'work_center_id' => 1,
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'dentro_rango' => true,
            'distancia_metros' => 12.5,
        ]);

        $html = view('filament.components.checkin-map', ['record' => $checada])->render();

        $this->assertStringContainsString('checkin-map-'.$checada->id, $html);
        $this->assertStringContainsString('data-lat="20.659698', $html);
        $this->assertStringContainsString('data-lng="-103.349609', $html);
    }

    public function test_empresa_se_puede_crear_desde_crud(): void
    {
        $admin = $this->crearAdmin();
        $this->actingAs($admin);

        Livewire::test(CreateCompany::class)
            ->fillForm([
                'nombre' => 'Empresa Norte',
                'direccion' => 'Av. Norte 500, Monterrey, N.L.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('companies', [
            'nombre' => 'Empresa Norte',
            'direccion' => 'Av. Norte 500, Monterrey, N.L.',
        ]);
    }

    public function test_empleado_se_puede_crear_con_datos_de_trabajador(): void
    {
        $admin = $this->crearAdmin();
        $this->actingAs($admin);

        $device = Device::create([
            'uuid' => 'android-abc123',
            'marca' => 'Samsung',
            'modelo' => 'Galaxy A54',
            'activo' => true,
        ]);

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'numero_empleado' => 'EMP-10',
                'nombre' => 'Carlos',
                'apellido_paterno' => 'Mendoza',
                'identificador_unico' => 'CURP123456789',
                'fecha_activacion' => '2026-01-15',
                'puesto' => 'Operador de producción',
                'area' => 'Línea 1',
                'horario_asignado' => '06:00 – 14:00',
                'device_id' => $device->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('employees', [
            'numero_empleado' => 'EMP-10',
            'identificador_unico' => 'CURP123456789',
            'fecha_activacion' => '2026-01-15 00:00:00',
            'puesto' => 'Operador de producción',
            'area' => 'Línea 1',
            'horario_asignado' => '06:00 – 14:00',
            'device_id' => $device->id,
        ]);
    }

    public function test_consulta_solo_ve_sus_checadas(): void
    {
        $admin = $this->crearAdmin();
        Role::firstOrCreate(['name' => 'consulta']);

        $empresa = Company::create(['nombre' => 'Empresa Principal']);

        $centroA = WorkCenter::create([
            'company_id' => $empresa->id,
            'nombre' => 'Centro A',
            'lat' => 20.1,
            'lng' => -103.1,
            'radio_metros' => 100,
            'activo' => true,
        ]);
        $centroB = WorkCenter::create([
            'company_id' => $empresa->id,
            'nombre' => 'Centro B',
            'lat' => 20.2,
            'lng' => -103.2,
            'radio_metros' => 100,
            'activo' => true,
        ]);

        $empleadoA = Employee::create([
            'numero_empleado' => 'EMP-A',
            'nombre' => 'Ana',
            'apellido_paterno' => 'Reyes',
            'work_center_id' => $centroA->id,
            'activo' => true,
        ]);
        $empleadoB = Employee::create([
            'numero_empleado' => 'EMP-B',
            'nombre' => 'Beto',
            'apellido_paterno' => 'Sosa',
            'work_center_id' => $centroB->id,
            'activo' => true,
        ]);

        $consulta = User::create([
            'name' => 'Consulta',
            'email' => 'consulta@test.test',
            'password' => 'password',
            'employee_id' => $empleadoA->id,
            'is_active' => true,
        ]);
        $consulta->assignRole('consulta');

        $checadaPropia = CheckIn::create([
            'employee_id' => $empleadoA->id,
            'user_id' => $consulta->id,
            'work_center_id' => $centroA->id,
            'tipo' => 'entrada',
            'lat' => 20.1,
            'lng' => -103.1,
            'dentro_rango' => true,
        ]);
        $checadaAjena = CheckIn::create([
            'employee_id' => $empleadoB->id,
            'user_id' => $admin->id,
            'work_center_id' => $centroB->id,
            'tipo' => 'entrada',
            'lat' => 20.2,
            'lng' => -103.2,
            'dentro_rango' => true,
        ]);

        $this->actingAs($consulta);

        $ids = CheckInResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($ids->contains($checadaPropia->id));
        $this->assertFalse($ids->contains($checadaAjena->id));
    }

    public function test_revisor_solo_ve_checadas_de_su_centro(): void
    {
        $this->crearAdmin();
        Role::firstOrCreate(['name' => 'revisor']);

        $empresa = Company::create(['nombre' => 'Empresa Principal']);

        $centroA = WorkCenter::create([
            'company_id' => $empresa->id,
            'nombre' => 'Centro A',
            'lat' => 20.1,
            'lng' => -103.1,
            'radio_metros' => 100,
            'activo' => true,
        ]);
        $centroB = WorkCenter::create([
            'company_id' => $empresa->id,
            'nombre' => 'Centro B',
            'lat' => 20.2,
            'lng' => -103.2,
            'radio_metros' => 100,
            'activo' => true,
        ]);

        $empleadoA = Employee::create([
            'numero_empleado' => 'EMP-A',
            'nombre' => 'Ana',
            'apellido_paterno' => 'Reyes',
            'work_center_id' => $centroA->id,
            'activo' => true,
        ]);
        $empleadoB = Employee::create([
            'numero_empleado' => 'EMP-B',
            'nombre' => 'Beto',
            'apellido_paterno' => 'Sosa',
            'work_center_id' => $centroB->id,
            'activo' => true,
        ]);

        $revisor = User::create([
            'name' => 'Revisor',
            'email' => 'revisor@test.test',
            'password' => 'password',
            'employee_id' => $empleadoA->id,
            'is_active' => true,
        ]);
        $revisor->assignRole('revisor');

        $checadaCentroA = CheckIn::create([
            'employee_id' => $empleadoA->id,
            'user_id' => $empleadoA->user()->first()?->id ?? $revisor->id,
            'work_center_id' => $centroA->id,
            'tipo' => 'entrada',
            'lat' => 20.1,
            'lng' => -103.1,
            'dentro_rango' => true,
        ]);
        $checadaCentroB = CheckIn::create([
            'employee_id' => $empleadoB->id,
            'user_id' => $revisor->id,
            'work_center_id' => $centroB->id,
            'tipo' => 'entrada',
            'lat' => 20.2,
            'lng' => -103.2,
            'dentro_rango' => true,
        ]);

        $this->actingAs($revisor);

        $ids = CheckInResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($ids->contains($checadaCentroA->id));
        $this->assertFalse($ids->contains($checadaCentroB->id));
    }

    public function test_filtros_checadas_por_rango_numero_y_nombre(): void
    {
        $admin = $this->crearAdmin();
        [$centro, $empleado] = $this->datosDemo($admin);

        $empleado2 = Employee::create([
            'numero_empleado' => 'EMP-2',
            'nombre' => 'María',
            'apellido_paterno' => 'López',
            'work_center_id' => $centro->id,
            'activo' => true,
        ]);

        CheckIn::create([
            'employee_id' => $empleado->id,
            'user_id' => $admin->id,
            'work_center_id' => $centro->id,
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'dentro_rango' => true,
            'created_at' => now()->subDays(2),
        ]);
        CheckIn::create([
            'employee_id' => $empleado2->id,
            'user_id' => $admin->id,
            'work_center_id' => $centro->id,
            'tipo' => 'salida',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'dentro_rango' => false,
            'created_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(ListCheckIns::class)
            ->filterTable('numero_empleado', ['value' => 'EMP-1'])
            ->assertCanSeeTableRecords(
                CheckIn::where('employee_id', $empleado->id)->get(),
            );

        Livewire::test(ListCheckIns::class)
            ->filterTable('nombre_empleado', ['value' => 'María'])
            ->assertCanSeeTableRecords(
                CheckIn::where('employee_id', $empleado2->id)->get(),
            );

        Livewire::test(ListCheckIns::class)
            ->filterTable('rango_fechas', [
                'desde' => now()->toDateString(),
                'hasta' => now()->toDateString(),
            ])
            ->assertCanSeeTableRecords(
                CheckIn::whereDate('created_at', today())->get(),
            );
    }

    public function test_exportar_checadas_excel_y_pdf(): void
    {
        $admin = $this->crearAdmin();
        [, $empleado] = $this->datosDemo($admin);

        CheckIn::create([
            'employee_id' => $empleado->id,
            'user_id' => $admin->id,
            'work_center_id' => 1,
            'tipo' => 'entrada',
            'lat' => 20.659698,
            'lng' => -103.349609,
            'dentro_rango' => true,
            'distancia_metros' => 12.5,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListCheckIns::class)
            ->assertActionVisible('exportar_excel')
            ->assertActionVisible('exportar_pdf');

        $records = CheckIn::with(['employee', 'user', 'device', 'workCenter', 'validator'])->get();

        $excel = CheckInExporter::excel($records, []);
        $this->assertEquals(200, $excel->getStatusCode());
        $this->assertStringContainsString('checadas_', (string) $excel->headers->get('content-disposition'));

        $pdf = CheckInExporter::pdf($records, [
            'rango_fechas' => ['desde' => null, 'hasta' => null],
        ]);
        $this->assertInstanceOf(StreamedResponse::class, $pdf);
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $pdf->headers->get('content-disposition'));

        ob_start();
        $pdf->sendContent();
        $contenido = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $contenido);
    }
}
