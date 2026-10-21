<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Shell of the public pages (home, about): translucent navigation over the
 * dark hero, site footer, motion bootstrap.
 */
class SiteLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.site');
    }
}
