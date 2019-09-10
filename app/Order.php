<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Status;

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
    $status = new Status;

    return [
      'id' => $this->id,
      'code' => $this->code,
      'email' => $this->email,
      'sum' => rand(50, 200) . 'zł',
      'created_at' => $this->created_at,
      'status' => [
        $status->payment_status[$this->payment_status]['color'],
        $status->shop_status[$this->shop_status]['color'],
        $status->delivery_status[$this->delivery_status]['color'],
      ]
    ];
  }

  public function paymentStatus($id)
  {
    $status = [
      'Oczekuje',
      'Realizacja',
      'Opłacone',
      'Niepowodzenie',
    ];
    return $status[$id];
  }

  public function shopStatus($id)
  {
    $status = [
      'Oczekuje',
      'Realizacja',
      'Gotowe',
      'Anulowane',
    ];
    return $status[$id];
  }

  public function deliveryStatus($id)
  {
    $status = [
      'Oczekuje',
      'W trasie',
      'Dostarczono',
      'Anulowane',
    ];
    return $status[$id];
  }
}