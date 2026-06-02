<?php

namespace App\Http\Controllers;

use App\Models\Page;

class LandingPageController extends Controller
{
    public function show($slug = 'home')
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->firstOrFail();
        return view('landing.show', compact('page'));
    }
}
