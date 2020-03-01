<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class AuthController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin/orders';

    public function loginForm()
    {
        return view('admin.auth.login');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
