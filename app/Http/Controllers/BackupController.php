<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ifsnop\Mysqldump\Mysqldump;

class BackupController extends Controller
{
    public function index()
    {
        $backups = Backup::orderBy('id', 'desc')->paginate(15);
        return view('backup.index', compact('backups'));
    }

    public function store()
    {
        $host = config('database.connections.mysql.host');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $db = config('database.connections.mysql.database');
        $port = config('database.connections.mysql.port', '3306');

        $fecha = date('Ymd_His');
        $file_name = "backup_{$db}_{$fecha}.sql";
        $ruta_relativa = "backups/" . $file_name;

        $backup_dir = sys_get_temp_dir() . '/intenergy_backups';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }

        $backup_file = $backup_dir . DIRECTORY_SEPARATOR . $file_name;

        try {
            $dump = new Mysqldump("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
            $dump->start($backup_file);

            Backup::create([
                'fecha_crea' => now(),
                'ruta' => $ruta_relativa
            ]);

            return response()->json([
                'success' => true,
                'mensaje' => 'Copia de seguridad realizada correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al crear la copia de seguridad: ' . $e->getMessage()
            ]);
        }
    }

    public function download($id)
    {
        $backup = Backup::findOrFail($id);
        $file = sys_get_temp_dir() . '/intenergy_backups/' . basename($backup->ruta);

        if (file_exists($file)) {
            return response()->download($file);
        } else {
            return back()->withErrors(['error' => 'El archivo de respaldo físico no fue encontrado en el servidor.']);
        }
    }
}
