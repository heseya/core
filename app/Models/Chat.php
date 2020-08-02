<?php

namespace App\Models;

use Illuminate\Support\Str;

class Chat extends Model
{
    const SYSTEM_INTERNAL = 0;
    const SYSTEM_EMAIL = 1;

    protected $fillable = [
        'system',
        'external_id',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'DESC');
    }

    public function getSnippetAttribute()
    {
        $message = $this->messages()->first();

        if (empty($message)) {
            return null;
        }

        if ($message->received === false) {
            $message->content = 'Ty: ' . $message->content;
        }

        return Str::limit(str_replace('&nbsp;', '', strip_tags($message->content)), 50);
    }

    public function getAvatarAttribute(): string
    {
        return '//www.gravatar.com/avatar/' . md5(strtolower(trim($this->external_id))) . '?d=mp&s=50x50';
    }

    public static function imap(): bool
    {
        return extension_loaded('imap');
    }
}
