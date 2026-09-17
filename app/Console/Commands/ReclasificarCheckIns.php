<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CheckIn;
use App\Services\ClasificadorHorario;
use Illuminate\Console\Command;

class ReclasificarCheckIns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkins:reclasificar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reclasifica las checadas históricas según el horario de cada empleado';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $actualizados = 0;
        $procesados = 0;

        CheckIn::query()
            ->whereHas('employee', function ($query): void {
                $query->whereNotNull('hora_entrada')
                    ->whereNotNull('hora_salida');
            })
            ->with('employee')
            ->chunkById(100, function ($checkIns) use (&$actualizados, &$procesados): void {
                foreach ($checkIns as $checkIn) {
                    $procesados++;

                    $nuevaClasificacion = ClasificadorHorario::clasificar(
                        $checkIn->employee,
                        $checkIn->fecha_dispositivo ?? $checkIn->created_at,
                        $checkIn->tipo,
                    );

                    if ($checkIn->clasificacion_horario !== $nuevaClasificacion) {
                        $checkIn->clasificacion_horario = $nuevaClasificacion;
                        $checkIn->saveQuietly();
                        $actualizados++;
                    }
                }
            });

        $this->info("Procesadas: {$procesados}");
        $this->info("Actualizadas: {$actualizados}");

        return self::SUCCESS;
    }
}
