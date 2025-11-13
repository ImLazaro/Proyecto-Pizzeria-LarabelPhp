<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens; 
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable; 

    protected $fillable = [
        'nombre',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}