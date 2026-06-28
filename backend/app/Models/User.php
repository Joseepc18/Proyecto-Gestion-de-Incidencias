<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'id_rol',
        'foto_perfil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    // Atajos de rol: centralizan el chequeo repetido nombre_rol === 'x'.
    public function esAdmin(): bool
    {
        return $this->rol && $this->rol->nombre_rol === 'admin';
    }

    public function esTecnico(): bool
    {
        return $this->rol && $this->rol->nombre_rol === 'tecnico';
    }

    public function esNormal(): bool
    {
        return $this->rol && $this->rol->nombre_rol === 'normal';
    }

    public function incidencias()
    {
        return $this->hasMany(Incidencia::class, 'id_usuario', 'id');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionIncidencia::class, 'id_usuario', 'id');
    }

    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'id_usuario', 'id');
    }

    public function evidencias()
    {
        return $this->hasMany(Evidencia::class, 'id_usuario', 'id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'id_usuario', 'id');
    }

    public function historialEstados()
    {
        return $this->hasMany(HistorialEstado::class, 'id_usuario', 'id');
    }

    public function bitacoraErrores()
    {
        return $this->hasMany(BitacoraError::class, 'id_usuario', 'id');
    }
}
