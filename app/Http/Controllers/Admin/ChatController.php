<?php

namespace App\Http\Controllers\Admin;

use App\Chat;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class ChatController extends Controller
{
    public function index()
    {
        $chats = Chat::paginate(20);

        return view('admin.chats.index', [
            'chats' => $chats,
        ]);
    }

    public function view(Chat $chat)
    {
        return view('admin.chats.view', [
            'chat' => $chat
        ]);
    }

    public function send(Request $request, Chat $chat)
    {
        $message = $chat->messages()->create([
            'content' => $request->message,
            'user_id' => auth()->user()->id,
        ]);

        $message->send();

        return redirect()->route('chats.view', $chat->id);
    }

    public function sync()
    {
        Artisan::call('emails:sync');

        return redirect()->route('chats');
    }
}
