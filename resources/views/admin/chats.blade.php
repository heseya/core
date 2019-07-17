@extends('admin/layout')

@section('title', 'Konwersacje')

@section('buttons')
  
@endsection

@section('content')
<ol id="chats" class="list list--chat"></ol>
@endsection

@section('scripts')
<script>
  updateChats()
</script>
@endsection
