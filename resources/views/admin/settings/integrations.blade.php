@extends('admin.layout')

@section('title', 'Dostępne integracje')

@section('buttons')

@endsection

@section('content')
<ol class="list list--settings">
    <li>
        Jeśli interesuje cię włączenie którejś z dostepnych integracji w twoim sklepie,
        skontaktuj się ze swoim opiekunek klienta lub napisz do nas na admin@example.com.
    </li>

    <li class="separator">Dostepne integracje</li>
    <li>
        <span>
            <div>Furgonetka.pl</div>
            <small>Nadawanie paczek oraz synchronizacja informacji o statusie dostawy.</small>
        </span>
    </li>
    <li>
        <span>
            <div>Facebook</div>
            <small>Synchronizacja produktów z katalogiem Facebook.</small>
        </span>
    </li>

    <li class="separator">Dostepne bramki płatności</li>
    <li>
        <span>
            <div>PayNow</div>
            <small>mBank</small>
        </span>
    </li>
    <li>
        <span>
            <div>Cinkciarz Pay</div>
            <small>Cinkciarz.pl</small>
        </span>
    </li>
</ol>
@endsection
