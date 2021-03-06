<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'theme', 'message', 'file_name'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

