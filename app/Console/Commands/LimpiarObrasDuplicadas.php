<?php

namespace App\Console\Commands;

use App\Models\Obra;
use App\Models\OrdenTrabajo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimpiarObrasDuplicadas extends Command
{
    protected $signature = 'obras:limpiar-duplicados {--ejecutar : Ejecutar la limpieza (sin esta opción solo muestra un reporte)}';
    protected $description = 'Encuentra y fusiona obras con nombres duplicados';

    public function handle()
    {
        $this->info('🔍 Buscando obras duplicadas...');
        $this->newLine();

        // Agrupar obras por nombre normalizado
        $obras = Obra::all()->groupBy(function($o) {
            return strtoupper(trim($o->nombre));
        });

        $duplicados = $obras->filter(function($group) {
            return $group->count() > 1;
        });

        if ($duplicados->isEmpty()) {
            $this->info('✅ No se encontraron obras duplicadas.');
            return 0;
        }

        $this->warn("⚠️  Se encontraron {$duplicados->count()} grupos de obras duplicadas:");
        $this->newLine();

        $totalEliminados = 0;

        foreach ($duplicados as $nombreNormalizado => $grupo) {
            $this->line("📋 Grupo: {$nombreNormalizado} ({$grupo->count()} registros)");
            
            // Ordenar por ID (el más antiguo primero)
            $grupo = $grupo->sortBy('id');
            $obraConservar = $grupo->first();
            $obrasBorrar = $grupo->slice(1);

            $this->table(
                ['ID', 'Nombre', 'Estado', 'Fecha Registro', 'Acción'],
                $grupo->map(function($obra) use ($obraConservar) {
                    return [
                        $obra->id,
                        $obra->nombre,
                        $obra->estado ? 'Activa' : 'Inactiva',
                        $obra->fecha_registro,
                        $obra->id === $obraConservar->id ? '✅ CONSERVAR' : '🗑️  ELIMINAR'
                    ];
                })
            );

            if ($this->option('ejecutar')) {
                // Actualizar las órdenes de trabajo que usan las obras duplicadas
                foreach ($obrasBorrar as $obraDuplicada) {
                    $ordenes = OrdenTrabajo::where('id_obra', $obraDuplicada->id)->count();
                    
                    if ($ordenes > 0) {
                        $this->line("   → Actualizando {$ordenes} orden(es) de trabajo...");
                        OrdenTrabajo::where('id_obra', $obraDuplicada->id)
                                   ->update(['id_obra' => $obraConservar->id]);
                    }
                    
                    // Eliminar la obra duplicada
                    $obraDuplicada->delete();
                    $totalEliminados++;
                    $this->line("   → Obra ID {$obraDuplicada->id} eliminada");
                }
                
                $this->info("   ✅ Grupo fusionado correctamente");
            }
            
            $this->newLine();
        }

        if ($this->option('ejecutar')) {
            $this->info("✅ Limpieza completada. Se eliminaron {$totalEliminados} obras duplicadas.");
        } else {
            $this->warn("⚠️  MODO REPORTE - No se realizaron cambios.");
            $this->info("💡 Para ejecutar la limpieza, usa: php artisan obras:limpiar-duplicados --ejecutar");
        }

        return 0;
    }
}
