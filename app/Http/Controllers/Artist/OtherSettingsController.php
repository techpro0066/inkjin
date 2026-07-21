<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\UserDetail;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OtherSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $userDetail = $request->user()->userDetail
            ?? UserDetail::create(['user_id' => $request->user()->id]);

        return view('artist.settings.other', [
            'userDetail' => $userDetail,
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'date_time_format' => ['required', Rule::in(['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'])],
            'size_unit' => ['required', Rule::in(['cm', 'in'])],
        ], [
            'timezone.required' => 'Please select a timezone.',
            'timezone.in' => 'Please select a valid timezone.',
            'date_time_format.required' => 'Please select a date format.',
            'size_unit.required' => 'Please select a unit.',
        ]);

        $userDetail = $request->user()->userDetail
            ?? UserDetail::create(['user_id' => $request->user()->id]);

        $userDetail->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Other settings updated successfully.',
        ]);
    }
}
