<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PublicPagesController extends Controller
{
    public function privacy(): View
    {
        return view('public.pages.privacy');
    }

    public function terms(): View
    {
        return view('public.pages.terms');
    }

    public function contact(): View
    {
        return view('public.pages.contact');
    }
}
