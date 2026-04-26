<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
