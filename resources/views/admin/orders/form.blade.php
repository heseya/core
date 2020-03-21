@extends('admin.layout')

@section('title', isset($order->code) ? 'Zamówienie ' . $order->code : 'Nowe zamówienie')

@section('buttons')

@endsection

@section('content')
<form method="post">
    @csrf

    <div class="columns">
        <div class="column">
            <h3>Szczegóły</h3>
            {{-- <div class="field">
                <label class="label" for="client_id">ID Klienta</label>
                <div class="control">
                    <input name="client_id" type="number" class="input @error('client_id') is-danger @enderror" autocomplete="off" value="{{ old('client_id') ?? $order->client_id ?? '' }}">
                </div>
                @error('client_id')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div> --}}
            <div class="field">
                <label class="label" for="email">E-mail</label>
                <div class="control">
                    <input name="email" type="email" class="input @error('email') is-danger @enderror" required value="{{ old('email') ?? $order->email ?? '' }}">
                </div>
                @error('email')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>
            <div class="field">
                <label class="label" for="delivery_tracking">Numer śledzenia</label>
                <div class="control">
                    <input name="delivery_tracking" class="input @error('delivery_tracking') is-danger @enderror" autocomplete="off" value="{{ old('delivery_tracking') ?? $order->delivery_tracking ?? '' }}">
                </div>
                @error('delivery_tracking')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>
            <div class="field">
                <label class="label" for="comment">Komentarz klienta</label>
                <div class="control">
                    <textarea name="comment" class="textarea @error('comment') is-danger @enderror" rows="8">{{ old('comment') ?? $order->comment ?? '' }}</textarea>
                </div>
                @error('comment')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="column">
            <h3>Adres dostawy</h3>
            <div class="field">
                <label class="label" for="deliveryAddress[name]">Imię i nazwisko</label>
                <div class="control">
                    <input name="deliveryAddress[name]" class="input @error('deliveryAddress[name]') is-danger @enderror" required autocomplete="off" value="{{ old('deliveryAddress.name') ?? $order->deliveryAddress->name ?? '' }}">
                </div>
                @error('deliveryAddress.name')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>
            <div class="field">
                <label class="label" for="deliveryAddress[address]">Ulica i numer domu</label>
                <div class="control">
                    <input name="deliveryAddress[address]" class="input @error('deliveryAddress[address]') is-danger @enderror" required autocomplete="off" value="{{ old('deliveryAddress.address') ?? $order->deliveryAddress->address ?? '' }}">
                </div>
                @error('deliveryAddress.address')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>
            <div class="columns has-no-margin-bottom">
                <div class="column is-4">
                    <div class="field">
                        <label class="label" for="deliveryAddress[zip]">Kod pocztowy</label>
                        <div class="control">
                            <input name="deliveryAddress[zip]" class="input @error('deliveryAddress[zip]') is-danger @enderror" required autocomplete="off" value="{{ old('deliveryAddress.zip') ?? $order->deliveryAddress->zip ?? '' }}">
                        </div>
                        @error('deliveryAddress.zip')
                            <p class="help is-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="column is-8">
                    <div class="field">
                        <label class="label" for="deliveryAddress[city]">Miasto</label>
                        <div class="control">
                            <input name="deliveryAddress[city]" class="input @error('deliveryAddress[city]') is-danger @enderror" required value="{{ old('deliveryAddress.city') ?? $order->deliveryAddress->city ?? '' }}">
                        </div>
                        @error('deliveryAddress.city')
                            <p class="help is-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="field">
                <label class="label" for="deliveryAddress[country]">Kraj (2 znaki)</label>
                <div class="control">
                    <input name="deliveryAddress[country]" maxlength="2" class="input @error('deliveryAddress[country]') is-danger @enderror" required value="{{ old('deliveryAddress.country') ?? $order->deliveryAddress->country ?? '' }}">
                </div>
                @error('deliveryAddress.country')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>
            <div class="field">
                <label class="label" for="deliveryAddress[phone]">Telefon</label>
                <div class="control">
                    <input name="deliveryAddress[phone]" type="tel" class="input @error('deliveryAddress[phone]') is-danger @enderror" value="{{ old('deliveryAddress.phone') ?? $order->deliveryAddress->phone ?? '' }}">
                </div>
                @error('deliveryAddress.phone')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div id="cart">
        <h3>Koszyk</h3>
    </div>

    <div class="buttons">
        <button class="button is-black">Zapisz</button>
        <button type="button" onclick="addItem('cart')" class="button">Dodaj produkt</button>
    </div>
</form>
@endsection
