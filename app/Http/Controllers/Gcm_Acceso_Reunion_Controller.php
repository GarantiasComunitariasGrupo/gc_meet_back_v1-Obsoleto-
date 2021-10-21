<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Gcm_Asistencia_Reunion;
use App\Models\Gcm_Registro_Representante;

class Gcm_Acceso_Reunion_Controller extends Controller
{

    public function getListaConvocados($idReunion)
    {
        $queryInvitados = DB::table('gcm_convocados_reunion')
        ->where('tipo', 1)
        ->where('id_reunion', $idReunion)
        ->select(DB::raw('*, NULL AS id_recurso'));

        $base = DB::query()->fromSub(function ($query) use ($queryInvitados, $idReunion) {
            $query->from('gcm_convocados_reunion AS cr')
            ->leftJoin(DB::raw('gcm_relaciones AS rl'), 'cr.id_relacion', '=', 'rl.id_relacion')
            ->leftJoin(DB::raw('gcm_recursos AS rs'), 'rl.id_recurso', '=', 'rs.id_recurso')
            ->leftJoin(DB::raw('gcm_roles AS rls'), 'rl.id_rol', '=', 'rls.id_rol')
            ->where('tipo', 0)
            ->where('id_reunion', $idReunion)
            ->unionAll($queryInvitados)->select([
                'cr.id_convocado_reunion', 'cr.id_reunion',
                'cr.id_usuario', 'cr.id_relacion', 'cr.fecha',
                'cr.tipo', 'rs.identificacion', 'rs.correo',
                'rs.razon_social', DB::raw('rls.descripcion AS rol'),
                'rl.participacion', 'rs.telefono', 'rs.id_recurso'
            ]);
        }, 'convocados')
        ->leftJoin(DB::raw('gcm_recursos AS rs'), 'convocados.identificacion', '=', 'rs.representante')
        ->get([DB::raw('convocados.*, rs.identificacion AS nit, rs.razon_social AS entity')]);

        return $base;
    }

    public function guardarAccesoReunion(Request $request)
    {
        $direccionIp = $_SERVER['REMOTE_ADDR'];
        $response = array();
        $datetime = date('Y-m-d h:i:s');

        $save = DB::statement("INSERT INTO gcm_asistencia_reuniones (id_convocado_reunion, fecha_ingreso, direccion_ip) VALUES ($request->id_convocado_reunion, '{$datetime}', '{$direccionIp}') ON DUPLICATE KEY UPDATE fecha_salida = '{$datetime}'");

        return response()->json(['ok' => ($save) ? true : false]);
    }

    public function guardarRepresentante(Request $request)
    {
        $response = array();
        $allowExt = array('PNG', 'JPG', 'JPEG', 'PDF');

        if ($request->hasFile('firma_png')) {

            $file = $request->file('firma_png');
            $ext = $file->getClientOriginalExtension();
            $filename = $request->id_reunion . '-' . $request->id_recurso . '-' . $request->identificacion . '.' . $ext;

            if (in_array(strtoupper($ext), $allowExt)) {

                $move = $file->move(public_path('storage\firmas'), $filename);
                chmod(public_path("storage/firmas/{$filename}"), 0555);

                if ($move) {
                    
                    $guardarRepresentante = Gcm_Registro_Representante::create([
                        'id_recurso' => $request->id_recurso,
                        'id_reunion' => $request->id_reunion,
                        'identificacion' => $request->identificacion,
                        'url_archivo' => 'firmas/' . $filename
                    ]);

                    $response = array('ok' => ($guardarRepresentante) ? true : false, 'response' => $guardarRepresentante);

                } else {
                    $response = array('ok' => false, 'response' => 'No se pudo mover el archivo');
                }

            } else {
                $response = array('ok' => false, 'response' => 'Extensión no permitida');
            }

        } else {
            $response = array('ok' => false, 'response' => 'No se recibió archivo');
        }

        return $response;
    }

    public function consultarRepresentante($idReunion, $idRecurso)
    {
        return Gcm_Registro_Representante::where('id_reunion', $idReunion)
        ->where('id_recurso', $idRecurso)
        ->where('estado', 1)
        ->first();
    }

    public function cancelarInvitacion(Request $request)
    {
        $response = array();

        $update = Gcm_Registro_Representante::where('estado', 1)
        ->where('id_recurso', $request->idRecurso)
        ->where('id_reunion', $request->idReunion)
        ->update(['estado' => 0]);

        $response = array('ok' => ($update) ? true : false, 'response' => ($update) ? 'La invitación se canceló correctamente' : 'Error al actualizar');
        
        return $response;
    }
}
