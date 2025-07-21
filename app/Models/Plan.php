<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use function Ramsey\Uuid\v1;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'task_limit',
    ];

    public function users(){
        return $this->hasMany(User::class);
    }
    public function orders(){
        return $this->hasMany(Order::class);
    }
}
