<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidenciaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Campos explícitos: no exponemos la fila entera, solo lo que el frontend usa.
        return [
            'id_incidencia' => $this->id_incidencia,
            'nombre_incidencia' => $this->nombre_incidencia,
            'descripcion_incidencia' => $this->descripcion_incidencia,
            'direccion_incidencia' => $this->direccion_incidencia,
            'latitud_incidencia' => $this->latitud_incidencia,
            'longitud_incidencia' => $this->longitud_incidencia,
            'prioridad_incidencia' => $this->prioridad_incidencia,
            'estado_incidencia' => $this->estado_incidencia,
            'id_ciudad' => $this->id_ciudad,
            'id_subtipo_incidencia' => $this->id_subtipo_incidencia,
            'id_usuario' => $this->id_usuario,
            'fecha_resolucion' => $this->fecha_resolucion,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Bandera de la incidencia: el reportador pidió reabrir y el admin aún no lo hace.
            // Habilita el botón "Reabrir" del admin (solo relevante cuando está RESUELTO).
            'reapertura_pendiente' => (bool) $this->reapertura_solicitada,
            // Relaciones: solo se incluyen si el controller las cargó (whenLoaded).
            'usuario' => $this->whenLoaded('usuario', fn () => new UserResource($this->usuario)),
            'subtipo' => $this->whenLoaded('subtipo'),
            'ciudad' => $this->whenLoaded('ciudad'),
            'evidencias' => $this->whenLoaded('evidencias'),
            'historial_estados' => $this->whenLoaded('historialEstados'),
            'asignaciones' => $this->whenLoaded('asignaciones'),
        ];
    }
}
