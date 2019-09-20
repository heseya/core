@extends('admin/layout')

@section('title', 'Zamówienie ' . $order->code)

@section('buttons')
{{-- <a href="/admin/orders/{{ $order->id }}/invoice" class="top-nav--button">
  <img class="icon" src="/img/icons/file.svg">
</a> --}}
@endsection

@section('content')
<div class="stats">
  <div class="stats__item">
    <img class="icon" src="/img/icons/money.svg">
    <select>
      @foreach ($status->payment_status as $id => $pstatus)
        <option onchange="updateStatus('payment', {{ $order->id }}, {{ $id }})">{{ $pstatus['name'] }}</option>
      @endforeach
    </select>
  </div>
  <div class="stats__item">
      <img class="icon" src="/img/icons/shop.svg">
      <select>
        @foreach ($status->shop_status as $sstatus)
          <option onchange="updateStatus('shop', {{ $order->id }}, {{ $id }})">{{ $sstatus['name'] }}</option>
        @endforeach
      </select>
    </div>
  <div class="stats__item">
    <img class="icon" src="/img/icons/delivery.svg">
    <select>
      @foreach ($status->delivery_status as $dstatus)
        <option onchange="updateStatus('delivery', {{ $order->id }}, {{ $id }})">{{ $dstatus['name'] }}</option>
      @endforeach
    </select>
  </div>
</div>

<div class="order">
  <div>
    <h3>Informacje</h3>
    <div>Dostawa: DPD</div>
    <div>Płatność: mTransfer</div>

    @if ($order->deliveryAddress)
      <h3>Adres dostawy</h3>
      <div>{{ $order->deliveryAddress->name }}</div>
      <div>{{ $order->deliveryAddress->address }}</div>
      <div>{{ $order->deliveryAddress->zip }} {{ $order->deliveryAddress->city }}</div>
      <div>{{ $order->deliveryAddress->country }}</div>
    @endif

    @if ($order->invoiceAddress)
      <h3>Adres do faktury</h3>
      <div>{{ $order->invoiceAddress->name }}</div>
      <div>{{ $order->invoiceAddress->address }}</div>
      <div>{{ $order->invoiceAddress->zip }} {{ $order->invoiceAddress->city }}</div>
      <div>{{ $order->invoiceAddress->country }}</div>
    @endif
  </div>

  <div class="cart">
    <h3 class="margin--left">Koszyk</h3>
    <div class="list">
      <li class="cart__item">
        <div class="cart__img">
          <img src="https://source.unsplash.com/collection/1085173/50x50?2">
        </div>
        <div class="cart__details">
          <div>Nazwa produktu</div>
          <small>200,00 zł</small>
        </div>
      </li>
      <li class="cart__item">
        <div class="cart__img">
          <img src="https://source.unsplash.com/collection/1085173/50x50?1">
        </div>
        <div class="cart__details">
          <div>Nazwa produktu <small>x 2</small></div>
          <small>200,00 zł</small>
        </div>
      </li>
      <li class="cart__item">
        <div class="cart__img">
          <img src="https://is5-ssl.mzstatic.com/image/thumb/Purple123/v4/e5/d3/74/e5d3743b-b2c0-ccb8-ddd8-ca69f5e40e55/AppIcon-0-1x_U007emarketing-0-0-GLES2_U002c0-512MB-sRGB-0-0-0-85-220-0-0-0-7.png/246x0w.jpg">
        </div>
        <div class="cart__details">
          <div>Dostawa</div>
          <small>17,00 zł</small>
        </div>
      </li>
      <li class="cart__summary">
        <div>
          <small>łącznie</small>
          <div>100,00 zł</div>
        </div>
        <div>
          <small>koszt produkcji</small>
          <div>32,00 zł</div>
        </div>
      </li>
    </div>
  </div>

</div>
@endsection

@section('scripts')

@endsection
