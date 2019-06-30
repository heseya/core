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
    'shop_status',
    'delivery',
    'delivery_status'
  ];

  public function address()
  {
    return $this->hasOne(Address::class);
  }

  public function view() 
  {
    $colors = [
      'grey',
      'orange',
      'green',
      'red'
    ];

    return [
      'id' => $this->id,
      'code' => $this->code,
      'email' => $this->email,
      'sum' => rand(50, 200) . 'zł',
      'created_at' => $this->created_at,
      'status' => [
        $colors[$this->payment_status],
        $colors[$this->shop_status],
        $colors[$this->delivery_status]
      ]
    ];
  }
}
