<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CheckIn;
use App\Models\Employee;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ClasificadorHorario
{
    private const MINUTOS_DIA = 1440;

    public static function clasificar(?Employee $employee, CarbonInterface $fechaHora, string $tipo): ?string
    {
        if ($employee === null || $employee->hora_entrada === null || $employee->hora_salida === null) {
            return null;
        }

        $minutos = self::minutosDesdeMedianoche($fechaHora);
        $entrada = self::horaAMinutos((string) $employee->hora_entrada);
        $salida = self::horaAMinutos((string) $employee->hora_salida);
        $esNocturno = $salida <= $entrada;

        if ($employee->descanso_inicio !== null && $employee->descanso_fin !== null) {
            $descansoInicio = self::horaAMinutos((string) $employee->descanso_inicio);
            $descansoFin = self::horaAMinutos((string) $employee->descanso_fin);

            $ventanaInicio = self::normalizarMinutos($descansoInicio - 30);
            $ventanaFin = self::normalizarMinutos($descansoFin + 30);

            if (self::estaEnVentana($minutos, $entrada, $salida) && self::estaEnVentana($minutos, $ventanaInicio, $ventanaFin)) {
                return CheckIn::CLASIFICACION_DESCANSO;
            }
        }

        $minutosComparacion = $minutos;
        $salidaComparacion = $salida;

        if ($esNocturno) {
            if ($minutosComparacion <= $salida) {
                $minutosComparacion += self::MINUTOS_DIA;
            }
            $salidaComparacion += self::MINUTOS_DIA;
        }

        return match ($tipo) {
            'entrada' => match (true) {
                $minutosComparacion < $entrada => CheckIn::CLASIFICACION_TEMPRANO,
                $minutosComparacion === $entrada => CheckIn::CLASIFICACION_A_TIEMPO,
                default => CheckIn::CLASIFICACION_TARDE,
            },
            'salida' => match (true) {
                $minutosComparacion < $salidaComparacion => CheckIn::CLASIFICACION_SALIDA_TEMPRANA,
                $minutosComparacion === $salidaComparacion => CheckIn::CLASIFICACION_A_TIEMPO,
                default => CheckIn::CLASIFICACION_SALIDA_TARDE,
            },
            default => null,
        };
    }

    private static function minutosDesdeMedianoche(CarbonInterface $fechaHora): int
    {
        return ($fechaHora->hour * 60) + $fechaHora->minute;
    }

    private static function horaAMinutos(string $hora): int
    {
        $carbon = Carbon::parse($hora);

        return ($carbon->hour * 60) + $carbon->minute;
    }

    private static function normalizarMinutos(int $minutos): int
    {
        return (($minutos % self::MINUTOS_DIA) + self::MINUTOS_DIA) % self::MINUTOS_DIA;
    }

    private static function estaEnVentana(int $minutos, int $inicio, int $fin): bool
    {
        if ($inicio <= $fin) {
            return $minutos >= $inicio && $minutos <= $fin;
        }

        return $minutos >= $inicio || $minutos <= $fin;
    }
}
