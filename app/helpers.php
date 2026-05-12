<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Default URL for an authenticated user (after login, guest middleware, home).
 */
function authenticated_home_url(?User $user = null): string
{
    $user ??= Auth::user();

    if (! $user) {
        return route('login', absolute: false);
    }

    if (! $user->hasVerifiedEmail()) {
        return route('verification.notice', absolute: false);
    }

    if ($user->role === 'user' && $user->must_set_password) {
        return route('user.bookings.index', ['set_password' => '1'], absolute: false);
    }

    return match ($user->role) {
        'admin' => route('admin.dashboard', absolute: false),
        'artist' => route('artist.dashboard', absolute: false),
        'user' => route('user.dashboard', absolute: false),
        default => abort(403, 'Access denied. You are not authorized to access this page.'),
    };
}

function imageUploader($file,$path)
{
        $extension = $file->getClientOriginalExtension();
        $extension=time().'.'.$extension;
        $file->move(public_path('uploads/'.$path.'/'),$extension);
        $fileName = '/uploads/'.$path.'/'.$extension;
        return $fileName;
}

/**
 * Generate a URL-friendly slug from a string
 * 
 * @param string $string
 * @return string
 */
function slugify($string)
{
    // Convert to lowercase
    $string = strtolower($string);
    
    // Replace spaces and special characters with hyphens
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    
    // Remove leading/trailing hyphens
    $string = trim($string, '-');
    
    // Remove multiple consecutive hyphens
    $string = preg_replace('/-+/', '-', $string);
    
    return $string;
}