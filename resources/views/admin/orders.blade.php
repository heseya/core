@extends('admin/layout')

@section('title', 'Zamówienia')

@section('buttons')
<a href="/admin/orders/add" class="top-nav--button">
  <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol id="orders" class="list list--orders"></ol>
@endsection

@section('scripts')
<script>
  updateOrders()
</script>
@endsection
