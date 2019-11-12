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

    // tylko dla niezalogowanych poza logout
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login()
    {
        return response()->view('admin/login', [
            'user' => Auth::user(),
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/admin/login');
    }
}
