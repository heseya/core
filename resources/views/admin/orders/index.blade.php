@extends('admin/layout')

@section('title', 'Zamówienia')

@section('buttons')

@endsection

@section('content')
<ol class="list list--orders">
    @foreach ($orders as $order)
        <a href="/admin/orders/{{ $order['id'] }}">
            <li class="clickable">
                <div>
                    <div>{{ $order['title'] }}</div>
                    <small>{{ $order['email'] }}</small>
                </div>
                <div class="sum">{{ $order['sum'] }}</div>
                <div class="status">
                    <div class="status-circle status-circle__{{ $order['status']['payment'] }}"></div>
                    <div class="status-circle status-circle__{{ $order['status']['shop'] }}"></div>
                    <div class="status-circle status-circle__{{ $order['status']['delivery'] }}"></div>
                </div>
            </li>
        </a>
    @endforeach
</ol>
@endsection
