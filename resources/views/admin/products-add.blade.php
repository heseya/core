@extends('admin/layout')

@section('title', 'Nowy Produkt')

@section('buttons')

@endsection

@section('content')
<form method="post">
  @csrf

  <div class="grid grid--2">
    <div>
      <div class="input sto">
        <label for="brand">Marka</label>
        <select type="text" name="brand">
          <option value="0">Depth</option>
          <option value="1">Depth Steel</option>
        </select>
      </div>
      <div class="input sto">
        <label for="name">Nazwa</label>
        <input type="text" name="name">
      </div>
      <div class="input sto">
        <label for="category">Kategoria</label>
        <select type="text" name="category">
          <option value="0">Depth</option>
          <option value="1">Depth Steel</option>
        </select>
      </div>
    </div>
    <div>
      <div class="input sto">
        <label for="description">Opis</label>
        <textarea name="desctiprion" rows="10"></textarea>
      </div>
    </div>
  </div>

  <div class="product-add">
    <p>Zdjęcia</p>
    <div id="tabs" class="gallery"></div>

    <div>
      <br><button class="button sto sto-mobile">Dodaj</button>
    </div>
  </div>
</form>
@endsection

@section('scripts')
<script src="/js/gallery.js"></script>
@endsection
