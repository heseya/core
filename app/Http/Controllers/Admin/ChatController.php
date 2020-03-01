<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Chat;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ChatController extends Controller
{
    public function index()
    {
        $chats = Chat::paginate(20);

        return view('admin/chats/index', [
            'chats' => $chats,
        ]);
    }

    public function view(Chat $chat)
    {
        return view('admin/chats/view', [
            'chat' => $chat
        ]);
    }

    public function send(Request $request, Chat $chat)
    {
        $chat->messages()->create([
            'content' => $request->message,
            'user_id' => Auth::user()->id,
        ]);

        return redirect()->route('chats.view', $chat->id);
    }
}
