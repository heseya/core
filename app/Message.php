<?php

namespace App;

use App\Chat;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public $timestamps = ['created_at'];

    protected $fillable = [
        'content',
        'user_id',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
