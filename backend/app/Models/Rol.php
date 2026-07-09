<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    // Nombres canónicos de los roles semilla; se usan en vez de literales sueltos para evitar typos silenciosos.
    public const NORMAL = 'normal';

    public const TECNICO = 'tecnico';

    public const ADMIN = 'admin';

    public const SUPER_ADMIN = 'super_admin';

    protected $table = 'roles';

    protected $primaryKey = 'id_rol';

    protected $fillable = [
        'nombre_rol',
        'descripcion_rol',
    ];

    public function usuarios()
    {
        return $this->hasMany(User::class, 'id_rol', 'id_rol');
    }

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'id_rol', 'id_permiso', 'id_rol', 'id_permiso');
    }
}
