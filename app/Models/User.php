<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Task;
use App\Models\Plan;
use App\Models\Order;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'plan_id',
        'is_admin',
    ];

    /**
     * Hidden when serialized.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casts.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /* ===================== Relationships ===================== */

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function tasks()
    {
        // Kolom default: user_id
        return $this->hasMany(Task::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
