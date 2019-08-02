@extends('admin/layout')

@section('title', 'Asortyment')

@section('buttons')
<a href="/admin/products/add" class="top-nav--button">
  <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<div class="products-categories">
  
</div>
<div id="products" class="products-list"></div>
@endsection

@section('scripts')
<script>
  updateProducts()
</script>
@endsection
