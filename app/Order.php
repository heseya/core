<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Address;

class Order extends Model
{
  protected $fillable = [
    'code',
    'payment',
    'payment_status',
    'delivery',
    'delivery_status'
  ];

  public function address()
  {
    return $this->hasOne(Address::class);
  }
}
