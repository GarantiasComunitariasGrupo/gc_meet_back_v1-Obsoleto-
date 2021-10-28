<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Gcm_Asistencia_Reunion;
use App\Models\Gcm_Convocado_Reunion;
use App\Models\Gcm_Recurso;
use App\Models\Gcm_Relacion;
use App\Models\Gcm_Log_Accion_Convocado;
use App\Models\Gcm_Restriccion_Rol_Representante;

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

        return response()->json($base);
    }

    public function guardarAccesoReunion(Request $request)
    {
        $response = array();
        $datetime = date('Y-m-d h:i:s');

        $save = DB::statement("INSERT INTO gcm_asistencia_reuniones (id_convocado_reunion, fecha_ingreso, estado) VALUES ($request->id_convocado_reunion, '{$datetime}', 1) ON DUPLICATE KEY UPDATE estado = 1");
        return response()->json(['ok' => ($save) ? true : false]);
    }

    public function actualizarAccesoReunion(Request $request)
    {
        $update = Gcm_Asistencia_Reunion::where('id_convocado_reunion', $request->id_convocado_reunion)
        ->update(['fecha_salida' => date('Y-m-d H:i:s'), 'estado' => 0]);

        return response()->json(['ok' => ($update) ? true : false]);
    }

    public function guardarRepresentante(Request $request)
    {
        $response = array();
        $allowExt = array('PNG', 'JPG', 'JPEG', 'PDF');

        $restricciones = $this->validacionRestricciones($request);

        if (count($restricciones) === 0) {

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

                            $anfitrion = Gcm_Convocado_Reunion::where('id_convocado_reunion', $request->id_convocado_reunion)->first();
                            $participacionRepresentante = $anfitrion->participacion;

                            $recurso = Gcm_Recurso::where('identificacion', $request->identificacion)->first();

                            if (!$recurso) {
                                $recurso = Gcm_Recurso::create([
                                    'identificacion' => $request->identificacion,
                                    'nombre' => $request->nombre,
                                    'correo' => $request->correo,
                                    'estado' => 1
                                ]);
                            }

                            $relacion = Gcm_Relacion::where('id_grupo', $request->id_grupo)
                            ->where('id_rol', $request->id_rol)
                            ->where('id_recurso', $recurso->id_recurso)
                            ->first();

                            if (!$relacion) {
                                $relacion = Gcm_Relacion::create([
                                    'id_grupo' => $request->id_grupo,
                                    'id_rol' => $request->id_rol,
                                    'id_recurso' => $recurso->id_recurso,
                                    'estado' => 1
                                ]);
                            }

                            $convocado = Gcm_Convocado_Reunion::create([
                                'id_reunion' => $request->id_reunion,
                                'representacion' => $request->id_convocado_reunion,
                                'id_relacion' => $relacion->id_relacion,
                                'fecha' => date('Y-m-d H:i:s'),
                                'tipo' => 0,
                                'participacion' => $participacionRepresentante,
                                'soporte' => 'firmas/' . $filename
                            ]);

                            DB::commit();
                            $response = array('ok' => true, 'response' => ['recurso' => $recurso, 'convocado' => $convocado]);

                        } catch (\Throwable $th) {
                            unlink(public_path('storage\firmas'), $filename);
                            $response = array('ok' => false, 'response' => $th->getMessage());
                            DB::rollback();
                        }

                    } else {
                        $response = array('ok' => false, 'response' => 'No se pudo mover el archivo');
                    }

                } else {
                    $response = array('ok' => false, 'response' => 'Extensión no permitida');
                }

            } else {
                $response = array('ok' => false, 'response' => 'No se recibió archivo');
            }
        } else {
            $response = array('ok' => false, 'response' => $restricciones, 'array' => true);
        }

        return response()->json($response);
    }

    public function validacionRestricciones($data)
    {
        $tipoReunion = DB::table(DB::raw('gcm_convocados_reunion AS gcr'))
        ->join(DB::raw('gcm_reuniones AS grns'), 'gcr.id_reunion', '=', 'grns.id_reunion')
        ->where('gcr.id_convocado_reunion', $data->id_convocado_reunion)
        ->select(['id_tipo_reunion'])
        ->first();

        $roles = DB::table(DB::raw('gcm_recursos AS grs'))
        ->join(DB::raw('gcm_relaciones AS grc'), 'grs.id_recurso', '=', 'grc.id_recurso')
        ->where('identificacion', $data->identificacion)
        ->where('grc.estado', 1)
        ->select(['id_rol'])
        ->get();

        $restricciones = Gcm_Restriccion_Rol_Representante::where('estado', 1)
        ->where('id_tipo_reunion', $tipoReunion->id_tipo_reunion)
        ->whereIn('id_rol', array_column($roles->toArray(), 'id_rol'))
        ->get();

        return $restricciones;
    }

    public function guardarLogConvocado($arrayParams)
    {
        $save = Gcm_Log_Accion_Convocado::create([
            'id_convocado_reunion' => $arrayParams['id_convocado_reunion'],
            'accion' => $arrayParams['accion'],
            'tabla' => $arrayParams['tabla'],
            'fecha' => date('Y-m-d H:i:s'),
            'lugar' => $arrayParams['lugar'],
            'detalle' => $arrayParams['detalle']
        ]);

        return ($save) ? true : false;
    }

    public function consultarRepresentante($idReunion, $idConvocadoReunion)
    {
        $base = DB::table(DB::raw('gcm_convocados_reunion AS gcr'))
        ->join(DB::raw('gcm_relaciones AS grc'), 'gcr.id_relacion', '=', 'grc.id_relacion')
        ->join(DB::raw('gcm_recursos AS grs'), 'grc.id_recurso', '=', 'grs.id_recurso')
        ->where('gcr.id_reunion', $idReunion)
        ->where('gcr.representacion', $idConvocadoReunion)
        ->select(['*'])
        ->first();

        return response()->json([
            'ok' => ($base) ? true : false,
            'response' => ($base) ? $base : 'No hay resultados'
        ]);
    }

    public function cancelarInvitacion(Request $request)
    {
        $response = array();

        $delete = Gcm_Convocado_Reunion::where('id_reunion', $request->idReunion)
        ->where('representacion', $request->idConvocadoReunion);

        $this->guardarLogConvocado(array(
            'id_convocado_reunion' => $request->idConvocadoReunion,
            'accion' => 2, 'tabla' => 'gcm_convocados_reunion',
            'lugar' => 'sala-espera-reunion/' . $request->idReunion,
            'detalle' => $delete->first()
        ));

        $delete->delete();

        return response()->json([
            'ok' => ($delete) ? true : false,
            'response' => ($delete) ? 'Se eliminó el convocado' : 'Error eliminando convocado'
        ]);
    }
}
