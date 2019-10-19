@extends('admin/layout')

@section('title', 'Nowa marka')

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
                <input type="text" name="link" placeholder="link" required>
            </div>
            <button class="button">Dodaj</button>
        </div>
    </div>
</form>
@endsection

@section('scripts')

@endsection

