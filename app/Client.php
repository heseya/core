<?php

namespace App;

use App\Chat;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
    ];

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }
}
