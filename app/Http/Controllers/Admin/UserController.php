<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $roleFilter = $request->get('role', 'all');
        
        $query = User::with('userDetail')->where('role', '!=', 'admin');
        
        if ($roleFilter !== 'all') {
            $query->where('role', $roleFilter);
        }
        
        $users = $query->orderBy('created_at', 'desc')->get();
        
        // If AJAX request, return JSON for DataTables
        if ($request->ajax()) {
            return response()->json([
                'data' => $users
            ]);
        }
        
        return view('admin.users.index', compact('users', 'roleFilter'));
    }

    /**
     * Get user details for modal.
     */
    public function show($id): JsonResponse
    {
        $user = User::with(['userDetail', 'availabilities'])->findOrFail($id);
        
        $userDetail = $user->userDetail;
        $timezone = $userDetail ? ($userDetail->timezone ?? 'UTC') : 'UTC';
        
        // Format availabilities with timezone conversion
        $availabilities = $user->availabilities->map(function ($availability) use ($timezone, $userDetail) {
            if ($userDetail && $timezone !== 'UTC') {
                try {
                    $startTime = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' ' . $availability->start_time, 'UTC')
                        ->setTimezone($timezone)
                        ->format('H:i');
                    $endTime = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' ' . $availability->end_time, 'UTC')
                        ->setTimezone($timezone)
                        ->format('H:i');
                    
                    return [
                        'id' => $availability->id,
                        'day_of_week' => $availability->day_of_week,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ];
                } catch (\Exception $e) {
                    // Fallback to original time if conversion fails
                    return [
                        'id' => $availability->id,
                        'day_of_week' => $availability->day_of_week,
                        'start_time' => $availability->start_time,
                        'end_time' => $availability->end_time,
                    ];
                }
            }
            
            return [
                'id' => $availability->id,
                'day_of_week' => $availability->day_of_week,
                'start_time' => $availability->start_time,
                'end_time' => $availability->end_time,
            ];
        });
        
        return response()->json([
            'success' => true,
            'user' => $user,
            'userDetail' => $userDetail ? $userDetail : null,
            'availabilities' => $availabilities,
        ]);
    }
}

