<?php

namespace App\Models;

use App\Enums\RolAsignacion;
use App\Notifications\RestablecerPasswordNotification;
use App\Notifications\VerificarEmailNotification;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, MustVerifyEmailTrait, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'id_rol',
        'foto_perfil',
        'email_verified_at',
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

    // Usamos plantillas Markdown propias (como el resto de correos), no las notificaciones nativas.
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerificarEmailNotification);
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    // Centraliza el whereHas('rol', ...) repetido en varios controllers.
    public function scopeConRol($query, string $rol)
    {
        return $query->whereHas('rol', fn ($q) => $q->where('nombre_rol', $rol));
    }

    // Usuarios cuyo rol tiene el permiso indicado; se usa para notificar a quienes pueden gestionar.
    public function scopeConPermiso($query, string $clave)
    {
        return $query->whereHas('rol.permisos', fn ($q) => $q->where('clave_permiso', $clave));
    }

    // super_admin es superset de admin: hereda todo el poder operativo (asignar, prioridades, borrar, etc.).
    public function esAdmin(): bool
    {
        return $this->rol && in_array($this->rol->nombre_rol, ['admin', 'super_admin'], true);
    }

    public function esSuperAdmin(): bool
    {
        return $this->rol && $this->rol->nombre_rol === 'super_admin';
    }

    // Fuente única de verdad para autorizar por permiso (middleware, policies y recurso de sesión).
    public function tienePermiso(string $clave): bool
    {
        return $this->rol && $this->rol->permisos->contains('clave_permiso', $clave);
    }

    // Lista de claves de permiso del rol; la consume UserResource para el frontend.
    public function permisosClaves(): array
    {
        return $this->rol ? $this->rol->permisos->pluck('clave_permiso')->all() : [];
    }

    public function esTecnico(): bool
    {
        return $this->rol && $this->rol->nombre_rol === 'tecnico';
    }

    public function esNormal(): bool
    {
        return $this->rol && $this->rol->nombre_rol === 'normal';
    }

    // Es el técnico RESPONSABLE de la incidencia (el de APOYO no cuenta). Fuente única para las policies.
    public function esResponsableDe(Incidencia $incidencia): bool
    {
        return $incidencia->asignaciones()
            ->where('id_usuario', $this->id)
            ->where('rol_asignado', RolAsignacion::Responsable->value)
            ->exists();
    }

    // Participa en la incidencia (ver detalle/chat): admin, el reportador o el técnico responsable. Fuente única para las policies.
    public function participaEn(Incidencia $incidencia): bool
    {
        return $this->esAdmin() || $incidencia->id_usuario === $this->id || $this->esResponsableDe($incidencia);
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

    public function historialEstados()
    {
        return $this->hasMany(HistorialEstado::class, 'id_usuario', 'id');
    }

    public function bitacoraErrores()
    {
        return $this->hasMany(BitacoraError::class, 'id_usuario', 'id');
    }
}
