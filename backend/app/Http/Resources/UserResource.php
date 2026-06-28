<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $esAdmin = $request->user()?->esAdmin();
        $esPropietario = $request->user()?->id === $this->id;
        $esAuthRoute = in_array($request->path(), ['api/login', 'api/register', 'api/auth/google/callback']);

        $data = parent::toArray($request);

        if (! $esAdmin && ! $esPropietario && ! $esAuthRoute) {
            unset($data['email']);
        }

        return $data;
    }
}
