<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
}
