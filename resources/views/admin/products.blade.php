@extends('admin/layout')

@section('title', 'Asortyment')

@section('buttons')
  
@endsection

@section('content')
  <div id="products" class="products-list"></div>
@endsection

@section('scripts')
  <script>
    updateProducts()
  </script>
@endsection

