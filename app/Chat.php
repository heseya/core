<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Chat extends Model
{
  protected $fillable = [
    'client_id',
    'type',
    'system_id'
  ];

  // typy czatów

  // 0 - czat wbudowany
  // 1 - e-mail
  // 2 - facebook

  public function client ()
  {
    return $this->belongsTo(Client::class);
  }

  public function snippet ()
  {
    return Str::limit('The quick brown fox jumps over the lazy dog', 20);
  }
}
