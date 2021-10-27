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
        $base = DB::table(DB::raw('gcm_convocados_reunion AS gcr'))
        ->join(DB::raw('gcm_relaciones AS grc'), 'gcr.id_relacion', '=', 'grc.id_relacion')
        ->join(DB::raw('gcm_recursos AS grs'), 'grc.id_recurso', '=', 'grs.id_recurso')
        ->join(DB::raw('gcm_roles AS grl'), 'grc.id_rol', '=', 'grl.id_rol')
        ->where(DB::raw('gcr.id_reunion'), $idReunion)
        ->select([
            DB::raw('grs.*'),
            DB::raw('grl.id_rol'),
            DB::raw('grl.descripcion AS rol'),
            'grc.id_grupo',
            'gcr.id_convocado_reunion',
            'gcr.nit',
            'gcr.razon_social',
            'gcr.participacion',
            'gcr.representacion'
        ])->get();

        return $base;
    }

    public function guardarAccesoReunion(Request $request)
    {
        $response = array();
        $datetime = date('Y-m-d h:i:s');

        $save = DB::statement("INSERT INTO gcm_asistencia_reuniones (id_convocado_reunion, fecha_ingreso, estado) VALUES ($request->id_convocado_reunion, '{$datetime}', 1) ON DUPLICATE KEY UPDATE estado = 1");

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
                    
                    DB::beginTransaction();

                    try {
                    
                        /**
                         * Consultar participacion de quien hace la invitación
                         */

                         /**
                          * Consultar recurso
                          */

                        /**
                         * Consultar relacion
                         */
                        
                        $recurso;
                        $relacion;
                        $convocado;

                        DB::commit();
                    } catch (\Throwable $th) {
                        DB::rollback();
                    }

                    // $response = array('ok' => ($guardarRepresentante) ? true : false, 'response' => $guardarRepresentante);

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
