<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subtask extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'task_id',
    ];

    public function task (){
        return $this->belongsTo(Task::class);
    }
}
