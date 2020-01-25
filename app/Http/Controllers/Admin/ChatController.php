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

        return response()->view('admin/chat/index', [
            'chats' => $chats,
        ]);
    }

    public function view(Chat $chat)
    {
        return response()->view('admin/chat/view', [
            'chat' => $chat
        ]);
    }

    public function send(Request $request, Chat $chat)
    {
        $chat->messages()->create([
            'content' => $request->message,
            'user_id' => Auth::user()->id,
        ]);

        return response()->redirectTo('/admin/chat/' . $chat->id);
    }
}
