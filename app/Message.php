<?php

namespace App;

use App\Chat;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'external_id',
        'content',
        'user_id',
        'created_at',
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
