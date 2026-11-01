<?php

namespace App\Models;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * Rol de usuario del admin. Los permisos son claves de
 * App\Support\Permissions::CATALOG guardadas en role_permissions.
 * 'root' no es un rol de esta tabla (ver AccessService).
 */
class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = ['slug', 'name', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    /** @return string[] */
    public function permissions(): array
    {
        return Capsule::table('role_permissions')->where('role_id', $this->id)->pluck('permission')->all();
    }

    /** Reemplaza los permisos del rol. @param string[] $keys */
    public function syncPermissions(array $keys): void
    {
        Capsule::connection()->transaction(function () use ($keys) {
            Capsule::table('role_permissions')->where('role_id', $this->id)->delete();
            $rows = array_map(fn ($k) => ['role_id' => $this->id, 'permission' => $k], array_values(array_unique($keys)));
            if ($rows) {
                Capsule::table('role_permissions')->insert($rows);
            }
        });
    }

    public function usersCount(): int
    {
        return User::where('role', $this->slug)->count();
    }
}
