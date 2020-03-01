<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Mail\NewAdmin;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.settings.users.index', [
            'users' => User::all(),
        ]);
    }

    public function view(User $user)
    {
        return view('admin.settings.users.view', [
            'user' => $user,
        ]);
    }

    public function createForm()
    {
        return view('admin.settings.users.create');
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|unique:users|email',
        ]);

        $password = Str::random(10);

        Mail::to($request->email)->send(
            new NewAdmin($request->email, $password)
        );

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
        ]);

        return redirect()->route('users.view', $user->id);
    }

    public function rbac(User $user, Request $request)
    {
        if (auth()->user()->can('manageUsers')) {
            $user->syncPermissions(array_keys($request->perms ?? []));
        }

        return redirect()->route('users.view', $user->id);
    }
}
