<?php

namespace App\Http\Controllers\Admin;

use App\Page;
use Parsedown;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function index()
    {
        return view('admin/pages/index', [
            'pages' => Page::paginate(20),
        ]);
    }

    public function view(Page $page)
    {
        return view('admin/pages/view', [
            'page' => $page,
            'selectLang' => app()->getLocale(),
        ]);
    }

    public function createForm()
    {
        return view('admin/pages/form');
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'public' => 'required',
            'lang' => ['required', 'size:2'],
            'slug' => [
                'required',
                'string',
                'max:128',
                'unique:pages',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
        ]);

        $page = Page::create($request->all());

        return redirect('/admin/pages/' . $page->slug);
    }

    public function updateForm(Page $page)
    {
        return view('admin/pages/form', [
            'page' => $page,
        ]);
    }

    public function update(Page $page, Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'public' => 'required',
            'slug' => [
                'required',
                'string',
                'max:128',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('pages')->ignore($page->slug, 'slug'),
            ],
        ]);

        $page->update($request->all());

        return redirect('/admin/pages/' . $page->slug . '?lang=' . app()->getLocale());
    }

    public function delete(Page $page)
    {
        $page->delete();

        return redirect('/admin/pages');
    }
}
