<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * The public homepage never touches the database: activities are members-only
 * content, so the landing page only presents the product itself.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome');
    }
}
