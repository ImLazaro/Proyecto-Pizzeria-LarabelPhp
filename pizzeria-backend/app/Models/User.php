<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens; 
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable; 

    protected $fillable = [
        'nombre',
        'password',
        'empleado_id',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            
            'password' => 'hashed',
        ];
    }

    public function rol()
{
    return $this->belongsTo(Rol::class, 'rol_id', 'id');
}

}