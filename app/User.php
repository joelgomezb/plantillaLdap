<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cn', 'mail', 'uid', 'givenName', 'sn', 'l', 'pager', 'title', 'gender', 'employeeType', 'st', 'ou', 'rol'
    ];

}
