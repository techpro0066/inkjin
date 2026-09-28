<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComingSoonController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->role === 'studio', 403);

        return view('studio.coming-soon');
    }
}
