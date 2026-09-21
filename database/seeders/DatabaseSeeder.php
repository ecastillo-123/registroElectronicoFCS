<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver_checadas',
            'validar_checadas',
            'gestionar_centros',
            'gestionar_empresas',
            'gestionar_empleados',
            'gestionar_usuarios',
            'gestionar_roles',
            'gestionar_jornadas',
            'gestionar_calendario',
            'gestionar_reglas',
            'gestionar_incidencias',
            'gestionar_correcciones',
            'ver_auditoria',
            'ver_reportes',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $revisor = Role::firstOrCreate(['name' => 'revisor']);
        $consulta = Role::firstOrCreate(['name' => 'consulta']);

        $admin->syncPermissions($permisos);
        $revisor->syncPermissions(['ver_checadas', 'validar_checadas', 'gestionar_incidencias', 'gestionar_correcciones', 'ver_reportes']);
        $consulta->syncPermissions(['ver_checadas']);

        $centro = WorkCenter::firstOrCreate(
            ['nombre' => 'Planta Matriz'],
            [
                'direccion' => 'Av. Central 1000, Guadalajara, Jal.',
                'lat' => 20.659698,
                'lng' => -103.349609,
                'radio_metros' => 150,
                'activo' => true,
            ]
        );

        $empleado = Employee::firstOrCreate(
            ['numero_empleado' => 'EMP-0001'],
            [
                'nombre' => 'Juan',
                'apellido_paterno' => 'Pérez',
                'apellido_materno' => 'Gómez',
                'email' => 'empleado@checador.test',
                'telefono' => '33-1234-5678',
                'work_center_id' => $centro->id,
                'activo' => true,
            ]
        );

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@checador.test'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'employee_id' => null,
                'is_active' => true,
            ]
        );
        $adminUser->assignRole('admin');

        $empleadoUser = User::firstOrCreate(
            ['email' => 'empleado@checador.test'],
            [
                'name' => 'Juan Pérez',
                'password' => 'password',
                'employee_id' => $empleado->id,
                'is_active' => true,
            ]
        );
        $empleadoUser->assignRole('consulta');
    }
}
