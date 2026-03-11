<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'role',
        'active',
        'email_verified'
    ];

    protected $hidden = [
        'password_hash',
        'email_verify_token'
    ];

    public function isActive(): bool
    {
        return (bool) $this->active;
    }
}
