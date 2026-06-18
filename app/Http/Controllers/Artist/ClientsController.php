<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Services\ArtistClientService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientsController extends Controller
{
    public function __construct(private ArtistClientService $clientService) {}

    public function index(Request $request): View
    {
        $payload = $this->clientService->buildForArtist((int) $request->user()->id);

        return view('artist.clients.index', [
            'clients' => $payload['clients'],
            'stats' => $payload['stats'],
            'currencySymbol' => '€',
        ]);
    }
}
