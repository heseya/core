<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Chat;
use App\Http\Controllers\Controller;

class ChatController extends Controller
{
    public function chats()
    {
        $chats = Chat::all();

        foreach ($chats as $chat) {
            $chat->client;
            $chat->avatar = $chat->avatar();
            $chat->snippet = ''; // $chat->snippet();
        }

        return response()->view('admin/chat/list', [
            'user' => Auth::user(),
            'chats' => $chats,
        ]);
    }

    public function chat(Chat $chat)
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
