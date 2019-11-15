<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Chat;
use App\Http\Controllers\Controller;

class ChatController extends Controller
{
    public function index()
    {
        $chats = Chat::all();

        foreach ($chats as $chat) {
            $chat->client;
            $chat->avatar = $chat->avatar();
            $chat->snippet = '';
            // $chat->snippet();
        }

        return response()->view('admin/chat/index', [
            'user' => Auth::user(),
            'chats' => $chats,
        ]);
    }

    public function single(Chat $chat)
    {
        return response()->view('admin/chat/single', [
            'user' => Auth::user(),
            'chat' => $chat,
            'messages' => $chat->messages,
            'client' => $chat->client,
            'type' => $chat->typeName(),
        ]);
    }
}
