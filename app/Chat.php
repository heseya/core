<?php

namespace App;

use App\Client;
use App\Message;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = [
        'type',
        'system_id',
        'client_id',
    ];

    // typy czatów        | system_id

    // 0 - czat wbudowany | null
    // 1 - e-mail         | adres e-mail
    // 2 - facebook       | id z facebooka

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
        return Str::limit('The quick brown fox jumps over the lazy dog', 40);
    }

    public function avatar()
    {
        switch($this->type) {
            case 1:
                return '//www.gravatar.com/avatar/' . md5(strtolower(trim($this->system_id))) . '?d=mp&s=50x50';
                break;

            default:
                return '//www.gravatar.com/avatar/2?d=mp&s=50x50';
        }
    }

    public function typeName()
    {
        return [
            0 => 'czat wewnętrzny',
            1 => 'email',
            2 => 'facebook',
        ][$this->type];
    }
}
