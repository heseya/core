<?php

namespace App\Http\Controllers;

use Auth;
use App\Chat;
use App\User;
use App\Brand;
use App\Order;
use App\Photo;
use App\Status;
use App\Product;
use App\Category;
use App\Mail\Test;
use App\Mail\NewAdmin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use anlutro\LaravelSettings\Facade as Setting;

class AdminController extends Controller
{
    public function orders()
    {
        return response()->view('admin/orders', [
            'user' => Auth::user(),
        ]);
    }

    public function order(Order $order)
    {
        return response()->view('admin/order', [
            'order' => $order,
            'status' => new Status,
            'user' => Auth::user(),
        ]);
    }

    public function ordersAdd()
    {
        return response()->view('admin/orders-add', [
            'user' => Auth::user(),
        ]);
    }

    public function products(Request $request)
    {
        return response()->view('admin/products', [
            'user' => Auth::user(),
        ]);
    }

    public function product(Product $product)
    {
        return response()->view('admin/product', [
            'product' => $product,
            'user' => Auth::user(),
        ]);
    }

    public function productsSingle()
    {
        return response()->view('admin/products-single', [
            'user' => Auth::user(),
        ]);
    }

    public function productsAdd()
    {
        return response()->view('admin/products-add', [
            'user' => Auth::user(),
            'brands' => Brand::all(),
            'categories' => Category::all(),
            'taxes' => [
                [
                    'id' => 0,
                    'name' => '23%',
                ],
                [
                    'id' => 1,
                    'name' => '8%',
                ]
            ]
        ]);
    }

    public function productsStore(Request $request)
    {
        $product = Product::create($request->all());

        foreach ($request->photos as $photo) {
            $product->photos()->attach(Photo::create([
                'url' => $photo,
            ]));
        }

        return redirect('/admin/products/' . $product->id);
    }

    public function login(Request $request)
    {
        return response()->view('admin/login', [
            'user' => Auth::user(),
        ]);
    }

    // USTAWIENIA
    public function settings(Request $request)
    {
        return response()->view('admin/settings', [
            'user' => Auth::user(),
        ]);
    }

    public function info(Request $request)
    {
        return response()->view('admin/settings/info', [
            'version' => '0.1',
            'user' => Auth::user(),
        ]);
    }

    public function email()
    {
        $email = config('mail.from.address');
        $gravatar = md5(strtolower(trim($email)));

        return response()->view('admin/settings/email', [
            'name' => config('mail.from.name'),
            'email' => $email,
            'gravatar' => $gravatar,
            'user' => Auth::user(),
            'imap' => extension_loaded('imap'),
        ]);
    }

    public function emailConfig()
    {
        $old = Setting::get('email', [
            'name' => '',
            'from' => [
                'host' => '',
                'user' => '',
                'password' => '',
                'port' => 587,
            ],
            'to' => [
                'host' => '',
                'user' => '',
                'password' => '',
                'port' => 993,
            ],
        ]);

        return response()->view('admin/settings/email-config', [
            'old' => $old,
            'user' => Auth::user(),
        ]);
    }

    public function emailConfigStore()
    {
        Setting::set('email', [
            'to' => [
                'user' => $_POST['to-user'],
                'password' => $_POST['to-password'],
                'host' => $_POST['to-host'],
                'port' => $_POST['to-port'] ?? 993,
            ],
            'from' => [
                'user' => $_POST['from-user'],
                'password' => $_POST['from-password'],
                'host' => $_POST['from-host'],
                'port' => $_POST['from-port'] ?? 587,
            ],
        ]);
        Setting::save();
        Artisan::call('config:cache');

        return redirect('/admin/settings/email');
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
        $password = str_random(8);

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

    public function notifications()
    {
        return response()->view('admin/settings/notifications', [
            'user' => Auth::user(),
        ]);
    }
}
