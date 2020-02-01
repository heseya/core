@extends('admin/layout')

@section('title', 'Zamówienia')

@section('buttons')
<a href="/admin/orders/create" class="top-nav--button">
    <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list list--orders">
    @foreach ($orders as $order)
        <a href="/admin/orders/{{ $order->code }}">
            <li class="clickable">
                <div>
                    <div>
                        {{ $order->deliveryAddress->name ? $order->deliveryAddress->name : $order->code }}
                    </div>
                    <small>{{ $order->code }} - {{ $order->email }}</small>
                </div>
                <div class="sum">{{ number_format($order->summary(), 2, ',', ' ') }} zł</div>
                <div class="status">
                    <div class="status-circle status-circle__{{ $status->payment_status[$order->payment_status]['color'] }}"></div>
                    <div class="status-circle status-circle__{{ $status->shop_status[$order->shop_status]['color'] }}"></div>
                    <div class="status-circle status-circle__{{ $status->delivery_status[$order->delivery_status]['color'] }}"></div>
                </div>
            </li>
        </a>
    @endforeach
</ol>

<br>
{{ $orders->links() }}
@endsection
