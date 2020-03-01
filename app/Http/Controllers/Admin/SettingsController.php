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
        return view('admin.settings.main', [
            'user' => Auth::user(),
        ]);
    }

    public function info()
    {
        return view('admin.settings.info');
    }

    public function docs()
    {
        return view('admin.settings.docs');
    }

    public function email()
    {
        $email = config('mail.address');
        $gravatar = md5(strtolower(trim($email)));

        return view('admin.settings.email.index', [
            'name' => config('mail.name'),
            'email' => $email,
            'gravatar' => $gravatar,
            'imap' => extension_loaded('imap'),
        ]);
    }

    public function emailTest()
    {
        Mail::to(Auth::user()->email)->send(new Test());

        return redirect()->route('email');
    }

    public function categories()
    {
        return view('admin.settings.categories.index', [
            'categories' => Category::all(),
        ]);
    }

    public function categoryCreateForm()
    {
        return view('admin.settings.categories.create');
    }

    public function categoryCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        ]);

        Category::create($request->all());

        return redirect()->route('categories');
    }

    public function brands()
    {
        return view('admin.settings.brands.index', [
            'brands' => Brand::all(),
        ]);
    }

    public function brandCreateForm()
    {
        return view('admin.settings.brands.create');
    }

    public function brandCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:brands|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        ]);

        Brand::create($request->all());

        return redirect()->route('brands');
    }

    public function brandUpdate(Request $request)
    {
        Brand::update($request->all());

        return redirect()->route('brands');
    }

    public function furgonetka()
    {
        return view('admin.settings.furgonetka');
    }
}
