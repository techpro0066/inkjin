<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function password(Request $request): View
    {
        return view('admin.settings.password', [
            'user' => $request->user(),
        ]);
    }
}
