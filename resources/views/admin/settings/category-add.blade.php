@extends('admin/layout')

@section('title', 'Nowa kategoria')

@section('buttons')

@endsection

@section('content')
<form method="post">
    @csrf
    <div class="grid grid--2">
        <div>
            <div class="input sto">
                <label for="name">Nazwa</label>
                <input type="text" name="name" required>
            </div>
            <div class="input sto">
                <label for="slug">Link</label>
                <input type="text" name="slug" required>
            </div>
            <div>
                <label for="public">Widoczna w sklepie</label>
                <input type="checkbox" name="public">
            </div>
            <button class="button">Dodaj</button>
        </div>
    </div>
</form>
@endsection

@section('scripts')

@endsection

