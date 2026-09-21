<?php

namespace App\Console\Commands;

use App\Models\OrdenTrabajo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimpiarOrdenesDuplicadas extends Command
{
    protected $signature = 'ordenes:limpiar-duplicados {--ejecutar : Ejecutar la limpieza (sin esta opción solo muestra un reporte)}';
    protected $description = 'Encuentra y elimina órdenes de trabajo duplicadas (misma combinación Proyecto + Obra)';

    public function handle()
    {
        $this->info('🔍 Buscando órdenes de trabajo duplicadas...');
        $this->newLine();

        // Agrupar órdenes por proyecto + obra
        $ordenes = OrdenTrabajo::with(['proyecto', 'obra'])
                               ->where('estado', 1)
                               ->get()
                               ->groupBy(function($orden) {
                                   return $orden->id_proyecto . '-' . $orden->id_obra;
                               });

        $duplicados = $ordenes->filter(function($group) {
            return $group->count() > 1;
        });

        if ($duplicados->isEmpty()) {
            $this->info('✅ No se encontraron órdenes de trabajo duplicadas.');
            return 0;
        }

        $this->warn("⚠️  Se encontraron {$duplicados->count()} grupos de órdenes duplicadas:");
        $this->newLine();

        $totalEliminadas = 0;

        foreach ($duplicados as $key => $grupo) {
            $primeraOrden = $grupo->first();
            $this->line("📋 Proyecto: {$primeraOrden->proyecto->nombre} | Obra: {$primeraOrden->obra->nombre}");
            $this->line("   ({$grupo->count()} órdenes encontradas)");
            
            // Ordenar por ID (la más antigua primero)
            $grupo = $grupo->sortBy('id_orden');
            $ordenConservar = $grupo->first();
            $ordenesEliminar = $grupo->slice(1);

            $this->table(
                ['ID', 'Identificador', 'Fecha Inicial', 'Costo', 'Estado', 'Acción'],
                $grupo->map(function($orden) use ($ordenConservar) {
                    return [
                        $orden->id_orden,
                        $orden->identificador,
                        $orden->fecha_inicial,
                        '$' . number_format($orden->costo, 2),
                        $orden->estadoOrden?->estado ?? 'N/A',
                        $orden->id_orden === $ordenConservar->id_orden ? '✅ CONSERVAR' : '🗑️  ELIMINAR'
                    ];
                })
            );

            if ($this->option('ejecutar')) {
                foreach ($ordenesEliminar as $ordenDuplicada) {
                    // Verificar si tiene pedidos, solicitudes o ejecuciones asociadas
                    $tienePedidos = DB::table('pedido_materiales')->where('id_orden', $ordenDuplicada->id_orden)->exists();
                    $tieneSolicitudes = DB::table('solicitud_materiales')->where('id_orden', $ordenDuplicada->id_orden)->exists();
                    $tieneEjecuciones = DB::table('ejecucion_obra')->where('id_orden', $ordenDuplicada->id_orden)->exists();
                    
                    if ($tienePedidos || $tieneSolicitudes || $tieneEjecuciones) {
                        $this->warn("   ⚠️  Orden ID {$ordenDuplicada->id_orden} tiene registros asociados. No se puede eliminar automáticamente.");
                        $this->line("      → Debe revisar manualmente y migrar los registros a la orden principal.");
                    } else {
                        // Marcar como inactiva (soft delete)
                        $ordenDuplicada->estado = 0;
                        $ordenDuplicada->save();
                        $totalEliminadas++;
                        $this->line("   → Orden ID {$ordenDuplicada->id_orden} ({$ordenDuplicada->identificador}) marcada como inactiva");
                    }
                }
                
                $this->info("   ✅ Grupo procesado");
            }
            
            $this->newLine();
        }

        if ($this->option('ejecutar')) {
            $this->info("✅ Limpieza completada. Se marcaron como inactivas {$totalEliminadas} órdenes duplicadas.");
            if ($totalEliminadas < $duplicados->sum(fn($g) => $g->count() - 1)) {
                $this->warn("⚠️  Algunas órdenes no se pudieron eliminar porque tienen registros asociados.");
                $this->info("💡 Revisa manualmente las órdenes marcadas con advertencia.");
            }
        } else {
            $this->warn("⚠️  MODO REPORTE - No se realizaron cambios.");
            $this->info("💡 Para ejecutar la limpieza, usa: php artisan ordenes:limpiar-duplicados --ejecutar");
        }

        return 0;
    }
}
