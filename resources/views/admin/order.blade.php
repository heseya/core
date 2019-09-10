@extends('admin/layout')

@section('title', 'Zamówienie ' . $order->code)

@section('buttons')
  
@endsection

@section('content')
<div class="stats">
  <div class="stats__item">
    <img class="icon" src="/img/icons/money.svg">
    <select>
      @foreach ($status->payment_status as $pstatus)
        <option>{{ $pstatus['name'] }}</option>
      @endforeach
    </select>
  </div>
  <div class="stats__item">
      <img class="icon" src="/img/icons/shop.svg">
      <select>
        @foreach ($status->shop_status as $pstatus)
          <option>{{ $pstatus['name'] }}</option>
        @endforeach
      </select>
    </div>
  <div class="stats__item">
    <img class="icon" src="/img/icons/delivery.svg">
    <select>
      @foreach ($status->delivery_status as $pstatus)
        <option>{{ $pstatus['name'] }}</option>
      @endforeach
    </select>
  </div>
</div>
<div class="order">
  <div>
    <h3>Adres dostawy</h3>
    <div>Wojtek Kowalski</div>
    <div>Gdańska 82/1</div>
    <div>82-200 Bydgoszcz</div>
  </div>
</div>
@endsection

@section('scripts')
  
@endsection
