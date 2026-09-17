<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\CheckIn;
use App\Models\Employee;
use App\Services\ClasificadorHorario;
use Carbon\Carbon;
use Tests\TestCase;

class ClasificadorHorarioTest extends TestCase
{
    public function test_entrada_temprano(): void
    {
        $employee = $this->employee(['hora_entrada' => '08:00:00', 'hora_salida' => '16:00:00']);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 07:30:00'), 'entrada');

        $this->assertSame(CheckIn::CLASIFICACION_TEMPRANO, $resultado);
    }

    public function test_entrada_a_tiempo(): void
    {
        $employee = $this->employee(['hora_entrada' => '08:00:00', 'hora_salida' => '16:00:00']);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 08:00:00'), 'entrada');

        $this->assertSame(CheckIn::CLASIFICACION_A_TIEMPO, $resultado);
    }

    public function test_entrada_tarde(): void
    {
        $employee = $this->employee(['hora_entrada' => '08:00:00', 'hora_salida' => '16:00:00']);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 08:01:00'), 'entrada');

        $this->assertSame(CheckIn::CLASIFICACION_TARDE, $resultado);
    }

    public function test_salida_temprana(): void
    {
        $employee = $this->employee(['hora_entrada' => '08:00:00', 'hora_salida' => '16:00:00']);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 15:30:00'), 'salida');

        $this->assertSame(CheckIn::CLASIFICACION_SALIDA_TEMPRANA, $resultado);
    }

    public function test_salida_a_tiempo(): void
    {
        $employee = $this->employee(['hora_entrada' => '08:00:00', 'hora_salida' => '16:00:00']);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 16:00:00'), 'salida');

        $this->assertSame(CheckIn::CLASIFICACION_A_TIEMPO, $resultado);
    }

    public function test_salida_tarde(): void
    {
        $employee = $this->employee(['hora_entrada' => '08:00:00', 'hora_salida' => '16:00:00']);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 16:01:00'), 'salida');

        $this->assertSame(CheckIn::CLASIFICACION_SALIDA_TARDE, $resultado);
    }

    public function test_dentro_de_la_ventana_de_descanso_devuelve_descanso(): void
    {
        $employee = $this->employee([
            'hora_entrada' => '08:00:00',
            'hora_salida' => '16:00:00',
            'descanso_inicio' => '12:00:00',
            'descanso_fin' => '13:00:00',
        ]);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 12:30:00'), 'entrada');

        $this->assertSame(CheckIn::CLASIFICACION_DESCANSO, $resultado);
    }

    public function test_limite_inferior_de_ventana_de_descanso_es_inclusivo(): void
    {
        $employee = $this->employee([
            'hora_entrada' => '08:00:00',
            'hora_salida' => '16:00:00',
            'descanso_inicio' => '12:00:00',
            'descanso_fin' => '13:00:00',
        ]);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 11:30:00'), 'entrada');

        $this->assertSame(CheckIn::CLASIFICACION_DESCANSO, $resultado);
    }

    public function test_limite_superior_de_ventana_de_descanso_es_inclusivo(): void
    {
        $employee = $this->employee([
            'hora_entrada' => '08:00:00',
            'hora_salida' => '16:00:00',
            'descanso_inicio' => '12:00:00',
            'descanso_fin' => '13:00:00',
        ]);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 13:30:00'), 'entrada');

        $this->assertSame(CheckIn::CLASIFICACION_DESCANSO, $resultado);
    }

    public function test_fuera_de_la_ventana_de_descanso_se_clasifica_por_tipo(): void
    {
        $employee = $this->employee([
            'hora_entrada' => '08:00:00',
            'hora_salida' => '16:00:00',
            'descanso_inicio' => '12:00:00',
            'descanso_fin' => '13:00:00',
        ]);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 11:00:00'), 'entrada');

        $this->assertSame(CheckIn::CLASIFICACION_TARDE, $resultado);
    }

    public function test_empleado_sin_horario_devuelve_null(): void
    {
        $employee = $this->employee([]);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 08:00:00'), 'entrada');

        $this->assertNull($resultado);
    }

    public function test_turno_nocturno_entrada_tarde_despues_de_la_medianoche(): void
    {
        $employee = $this->employee(['hora_entrada' => '22:00:00', 'hora_salida' => '06:00:00']);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 23:00:00'), 'entrada');

        $this->assertSame(CheckIn::CLASIFICACION_TARDE, $resultado);
    }

    public function test_turno_nocturno_salida_temprana_antes_de_la_medianoche(): void
    {
        $employee = $this->employee(['hora_entrada' => '22:00:00', 'hora_salida' => '06:00:00']);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-16 05:00:00'), 'salida');

        $this->assertSame(CheckIn::CLASIFICACION_SALIDA_TEMPRANA, $resultado);
    }

    public function test_turno_nocturno_entrada_temprana_antes_del_inicio(): void
    {
        $employee = $this->employee(['hora_entrada' => '22:00:00', 'hora_salida' => '06:00:00']);

        $resultado = ClasificadorHorario::clasificar($employee, Carbon::parse('2026-09-15 21:30:00'), 'entrada');

        $this->assertSame(CheckIn::CLASIFICACION_TEMPRANO, $resultado);
    }

    private function employee(array $atributos): Employee
    {
        return new Employee(array_merge([
            'numero_empleado' => 'EMP-TEST',
            'nombre' => 'Test',
            'apellido_paterno' => 'Test',
        ], $atributos));
    }
}
