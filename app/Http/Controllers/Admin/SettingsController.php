<?php

namespace App\Http\Controllers\Admin;

use App\Chat;
use App\User;
use App\Brand;
use App\Category;
use App\Mail\Test;
use App\Mail\NewAdmin;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function settings()
    {
        return view('admin.settings.main', [
            'user' => auth()->user(),
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
            'imap' => Chat::imap(),
        ]);
    }

    public function emailTest()
    {
        Mail::to(auth()->user()->email)->send(new Test());

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
        return view('admin.settings.categories.form');
    }

    public function categoryUpdateForm(Category $category)
    {
        return view('admin.settings.categories.form', [
            'category' => $category,
        ]);
    }

    public function categoryCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        ]);

        $values = $request->all();
        $values['public'] = $request->boolean('public');

        Category::create($values);

        return redirect()->route('categories');
    }

    public function categoryUpdate(Category $category, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories')->ignore($category->slug, 'slug'),
            ],
        ]);

        $values = $request->all();
        $values['public'] = $request->boolean('public');

        $category->update($values);

        return redirect()->route('categories');
    }

    public function categoryDelete(Category $category)
    {
        if ($category->products()->exists()) {
            return redirect()->back()->withErrors([
                'has-products' => __('relations.category'),
            ]);
        }

        $category->delete();

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
        return view('admin.settings.brands.form');
    }

    public function brandUpdateForm(Brand $brand)
    {
        return view('admin.settings.brands.form', [
            'brand' => $brand
        ]);
    }

    public function brandCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:brands|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        ]);

        $values = $request->all();
        $values['public'] = $request->boolean('public');

        Brand::create($values);

        return redirect()->route('brands');
    }

    public function brandUpdate(Brand $brand, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('brands')->ignore($brand->slug, 'slug'),
            ],
        ]);

        $values = $request->all();
        $values['public'] = $request->boolean('public');

        $brand->update($values);

        return redirect()->route('brands');
    }

    public function brandDelete(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return redirect()->back()->withErrors([
                'has-products' => __('relations.brand'),
            ]);
        }

        $brand->delete();

        return redirect()->route('brands');
    }

    public function furgonetka()
    {
        return view('admin.settings.furgonetka');
    }
}
