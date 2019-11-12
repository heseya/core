<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\User;
use App\Brand;
use App\Category;
use App\Mail\Test;
use App\Mail\NewAdmin;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function settings()
    {
        return response()->view('admin/settings/main', [
            'user' => Auth::user(),
        ]);
    }

    public function info()
    {
        return response()->view('admin/settings/info', [
            'version' => '0.1',
            'user' => Auth::user(),
        ]);
    }

    public function email()
    {
        $email = config('mail.address');
        $gravatar = md5(strtolower(trim($email)));

        return response()->view('admin/settings/email', [
            'name' => config('mail.name'),
            'email' => $email,
            'gravatar' => $gravatar,
            'user' => Auth::user(),
            'imap' => extension_loaded('imap'),
        ]);
    }

    public function emailTest()
    {
        Mail::to(Auth::user()->email)->send(new Test());

        return redirect('/admin/settings/email');
    }

    public function accounts()
    {
        $accounts = User::all();

        return response()->view('admin/settings/accounts', [
            'accounts' => $accounts,
            'user' => Auth::user(),
        ]);
    }

    public function accountsAdd()
    {
        return response()->view('admin/settings/accounts-add', [
            'user' => Auth::user(),
        ]);
    }

    public function accountsStore()
    {
        $password = Str::random(10);

        Mail::to($_POST['email'])->send(new NewAdmin($_POST['email'], $password));

        User::create([
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => Hash::make($password),
        ]);

        return redirect('/admin/settings/accounts');
    }

    public function categories()
    {
        return response()->view('admin/settings/categories', [
            'user' => Auth::user(),
            'categories' => Category::all(),
        ]);
    }

    public function categoryAdd()
    {
        return response()->view('admin/settings/category-add', [
            'user' => Auth::user(),
        ]);
    }

    public function categoryStore(Request $request)
    {
        Category::create($request->all());

        return redirect('/admin/settings/categories');
    }

    public function categoryUpdate(Request $request)
    {
        Category::update($request->all());

        return redirect('/admin/settings/categories');
    }

    public function brands()
    {
        return response()->view('admin/settings/brands', [
            'user' => Auth::user(),
            'brands' => Brand::all(),
        ]);
    }

    public function brandAdd()
    {
        return response()->view('admin/settings/brand-add', [
            'user' => Auth::user(),
        ]);
    }

    public function brandStore(Request $request)
    {
        Brand::create($request->all());

        return redirect('/admin/settings/brands');
    }

    public function brandUpdate(Request $request)
    {
        Brand::update($request->all());

        return redirect('/admin/settings/brands');
    }
}