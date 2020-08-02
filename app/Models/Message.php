<?php

namespace App\Models;

use App\Mail\Message as MailMessage;
use Illuminate\Support\Facades\Mail;

class Message extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'received',
        'external_id',
        'content',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'received' => 'boolean',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function send(): void
    {
        Mail::to($this->chat->external_id)
            ->send(new MailMessage($this->content));
    }
}
