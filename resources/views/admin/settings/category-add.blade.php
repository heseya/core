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
                <input type="text" name="name" placeholder="Nazwa" required>
            </div>
            <div class="input sto">
                <input type="text" name="slug" placeholder="slug" required>
            </div>
            <button class="button">Dodaj</button>
        </div>
    </div>
</form>
@endsection

@section('scripts')

@endsection

