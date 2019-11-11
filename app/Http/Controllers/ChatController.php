<?php

namespace App\Http\Controllers;

use Auth;
use App\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function chats()
    {
        return response()->view('chat/chats', [
            'user' => Auth::user(),
        ]);
    }

    public function chatsList()
    {
        $chats = Chat::all();

        foreach ($chats as $chat) {
            $chat->client;
            $chat->avatar = $chat->avatar();
            $chat->snippet = ''; // $chat->snippet();
        }

        return response()->json($chats);
    }

    public function chat(Chat $chat)
    {
        return response()->view('chat/chat', [
            'user' => Auth::user(),
            'chat' => $chat,
            'messages' => $chat->messages,
            'client' => $chat->client,
            'type' => $chat->typeName(),
        ]);
    }
}
