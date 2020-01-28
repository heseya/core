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
        return response()->view('admin/settings/info');
    }

    public function email()
    {
        $email = config('mail.address');
        $gravatar = md5(strtolower(trim($email)));

        return response()->view('admin/settings/email/index', [
            'name' => config('mail.name'),
            'email' => $email,
            'gravatar' => $gravatar,
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
        return response()->view('admin/settings/accounts/index', [
            'accounts' => User::all(),
        ]);
    }

    public function accountsCreateForm()
    {
        return response()->view('admin/settings/accounts/create');
    }

    public function accountsCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|unique:users|email',
        ]);

        $password = Str::random(10);

        Mail::to($request->email)->send(
            new NewAdmin($request->email, $password)
        );

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
        ]);

        return redirect('/admin/settings/accounts');
    }

    public function categories()
    {
        return response()->view('admin/settings/categories/index', [
            'categories' => Category::all(),
        ]);
    }

    public function categoryCreateForm()
    {
        return response()->view('admin/settings/categories/create');
    }

    public function categoryCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        ]);

        Category::create($request->all());

        return redirect('/admin/settings/categories');
    }

    public function brands()
    {
        return response()->view('admin/settings/brands/index', [
            'brands' => Brand::all(),
        ]);
    }

    public function brandCreateForm()
    {
        return response()->view('admin/settings/brands/create');
    }

    public function brandCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:brands|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        ]);

        Brand::create($request->all());

        return redirect('/admin/settings/brands');
    }

    public function brandUpdate(Request $request)
    {
        Brand::update($request->all());

        return redirect('/admin/settings/brands');
    }

    public function furgonetka()
    {
        return response()->view('admin/settings/furgonetka');
    }
}
