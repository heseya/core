@extends('admin.layout')

@section('title', 'Zamówienie ' . $order->code)

@section('buttons')
{{-- <a href="/admin/orders/{{ $order->id - 1 }}" class="top-nav--button">
    <img class="icon" src="/img/icons/left.svg">
</a>
<a href="/admin/orders/{{ $order->id + 1 }}" class="top-nav--button">
    <img class="icon" src="/img/icons/right.svg">
</a> --}}
<a class="top-nav--button" onclick="payment('{{ $order->code }}')">
    <img class="icon" src="/img/icons/cash.svg">
</a>
@can('manageOrders')
<a href="{{ route('orders.update', $order->code) }}" class="top-nav--button">
    <img class="icon" src="/img/icons/pencil.svg">
</a>
@endcan
@endsection

@section('content')
<div class="stats">
    <div class="stats__item">
        <img class="icon" src="/img/icons/money.svg">
        {{ \App\Status::payment($order->payment_status)['name'] }}
    </div>
    <div class="stats__item">
        <img class="icon" src="/img/icons/shop.svg">
        <select id="shop_status">
            @foreach (\App\Status::shopAll() as $id => $sstatus)
            <option value="{{ $id }}" {{ $order->shop_status == $id ? 'selected' : '' }}>
                {{ $sstatus['name'] }}
            </option>
            @endforeach
        </select>
        </div>
    <div class="stats__item">
        <img class="icon" src="/img/icons/delivery.svg">
        {{ \App\Status::delivery($order->delivery_status)['name'] }}
    </div>
</div>

<div class="columns is-multiline">
    <div class="column is-half">
        @if ($order->deliveryAddress)
            <h3>Adres dostawy</h3>
            <div>{{ $order->deliveryAddress->name }}</div>
            <div>{{ $order->deliveryAddress->address }}</div>
            <div>{{ $order->deliveryAddress->zip }} {{ $order->deliveryAddress->city }}</div>
            <div>{{ $order->deliveryAddress->country }}</div>
            <br>
        @endif

        @if ($order->invoiceAddress)
            <h3>Dane do faktury</h3>
            <div>{{ $order->invoiceAddress->name }}</div>
            <div>{{ $order->invoiceAddress->address }}</div>
            <div>{{ $order->invoiceAddress->zip }} {{ $order->invoiceAddress->city }}</div>
            <div>{{ $order->invoiceAddress->country }}</div>
            <br>
        @endif

        <h3>Metoda dostawy</h3>
        <div>{{ $order->delivery_method }}</div>

        @isset($order->comment)
            <h3>Komentarz klienta</h3>
            <div>{{ $order->comment }}</div>
        @endisset
    </div>

    <div class="column is-half">
        <h3>Koszyk</h3>
        <div class="menu">
            @foreach ($order->items as $item)
                <ul class="menu-list">
                    <li>
                        <b>
                            {{ $item->product->name }}
                            @if ($item->qty != 1)
                                <small class="cart__small">
                                    x {{ $item->qty }}
                                </small>
                            @endif
                        </b>
                        <small>{{ number_format($item->price, 2, ',', ' ') }} zł</small>
                        <ul>
                            @foreach ($item->schemaItems as $subItem)
                                <li>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    {{ $subItem->schema->name . ': ' . $subItem->item->name }}
                                    @if(false)
                                    @if ($subItem->qty != 1)
                                        <small class="cart__small">
                                            x {{ $subItem->qty }}
                                        </small>
                                    @endif
                                    @if ($subItem->price != 0)
                                        <small>{{ number_format($subItem->price, 2, ',', ' ') }} zł</small>
                                    @endif
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </li>
                </ul>
            @endforeach
            <div class="cart__summary">
                <div>
                    <small class="cart__small">Łącznie</small>
                    <div>{{ number_format($order->summary(), 2, ',', ' ') }} zł</div>
                </div>
            </div>
        </div>
    </div>

    {{-- <div>
        <h3 class="margin--left">Dokumenty</h3>
        <div class="list">
            <li class="clickable marginles">
                <div>Faktura VAT D109-19</div>
            </li>
            <li class="clickable marginles">
                <div>List przewozowy</div>
            </li>
        </div>
    </div> --}}

    <div class="column is-half">
        <h3>Linia czasu</h3>
        <div class="timeline">
            @foreach ($order->logs as $log)
                <div class="timeline__block">
                    <div class="marker"></div>
                    <div class="timeline-content">
                        <p>{{ $log['content'] }}</p>
                        <small>{{ $log['user'] }}, {{ $log['created_at'] }}</small>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="column is-half">
        <h3>Notatki</h3>
        <button onclick="window.htmlModal(
            `<form action='{{ route('orders.note', $order) }}' method='POST'>
                <input type='hidden' name='_token' value='{{ csrf_token() }}'>
                <div class='field'>
                    <label class='label' for='message'>Treść notatki</label>
                    <div class='control'>
                        <textarea name='message' class='textarea' rows='5'>{{ old('message') ?? '' }}</textarea>
                    </div>
                </div>
                <button class='button is-black'>Dodaj</button>
            </form>`)" class="top-nav--button">
            <img class="icon" src="/img/icons/plus.svg">
        </button>
        @error('message')
            <p class='help is-danger'>{{ $message }}</p>
        @enderror
        <div class="timeline">
            @foreach ($order->notes as $note)
                <div class="timeline__block">
                    <div class="marker"></div>
                    <div class="timeline-content">
                        <p>{{ $note['message'] }}</p>
                        <small>{{ $note->user['name'] }}, {{ $note['created_at'] }}</small>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

<script>
    window.order_code = "{{ $order->code }}"
</script>
@endsection
