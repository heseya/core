<?php

namespace App;

use App\Client;
use App\Message;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    const SYSTEM_INTERNAL = 0;
    const SYSTEM_EMAIL = 1;

    protected $fillable = [
        'system',
        'external_id',
        'client_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'DESC');
    }

    public function snippet()
    {
        $message = $this->messages()->first();

        if (empty($message)) {
            return null;
        }

        if ($message->user_id === null) {
            return '<spam class="unread">' . Str::limit($message->content, 40) . '</spam>';
        }

        return Str::limit($message->user->name . ': ' . $message->content, 40);
    }

    public function avatar(): string
    {
        switch($this->type) {
            case self::SYSTEM_EMAIL:
                return '//www.gravatar.com/avatar/' . md5(strtolower(trim($this->system_id))) . '?d=mp&s=50x50';
                break;

            default:
                return '//www.gravatar.com/avatar/2?d=mp&s=50x50';
        }
    }

    public static function imap(): bool
    {
        return extension_loaded('imap');
    }
}
