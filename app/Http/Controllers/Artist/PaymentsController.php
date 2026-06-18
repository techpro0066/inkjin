<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Services\ArtistPaymentsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentsController extends Controller
{
    public function __construct(private ArtistPaymentsService $paymentsService) {}

    public function index(Request $request): View
    {
        $payload = $this->paymentsService->buildForArtist((int) $request->user()->id);

        return view('artist.payments.index', [
            'payments' => $payload['payments'],
            'stats' => $payload['stats'],
        ]);
    }
}
