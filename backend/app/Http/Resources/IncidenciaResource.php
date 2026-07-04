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
            // Habilita el botón "Reabrir" del admin cuando el reportador ya lo pidió (solo relevante en RESUELTO).
            'reapertura_pendiente' => (bool) $this->reapertura_solicitada,
            // Admin que reclamó la incidencia (columna "Atendido por" y botón "Archivar").
            'id_admin_atiende' => $this->id_admin_atiende,
            // Lease del candado: el último latido y si ya venció, para decidir el botón "Forzar liberar".
            'reclamo_visto_en' => $this->reclamo_visto_en,
            'reclamo_vencido' => $this->reclamoVencido(),
            // Relaciones: solo se incluyen si el controller las cargó.
            'usuario' => $this->whenLoaded('usuario', fn () => new UserResource($this->usuario)),
            'subtipo' => $this->whenLoaded('subtipo'),
            'ciudad' => $this->whenLoaded('ciudad'),
            'evidencias' => $this->whenLoaded('evidencias'),
            'historial_estados' => $this->whenLoaded('historialEstados'),
            'asignaciones' => $this->whenLoaded('asignaciones'),
            'admin_atiende' => $this->whenLoaded('adminAtiende', fn () => $this->adminAtiende ? new UserResource($this->adminAtiende) : null),
        ];
    }
}
