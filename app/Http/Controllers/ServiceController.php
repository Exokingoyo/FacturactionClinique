<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return view('services.index', ['services' => Service::orderBy('nom')->get()]);
    }

    public function store(Request $request)
    {
        Service::create($this->data($request));
        return back()->with('ok', 'Service ajouté.');
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->data($request));
        return back()->with('ok', 'Service modifié.');
    }

    public function destroy(Service $service)
    {
        $service->delete(); // les anciennes factures gardent leur désignation et prix
        return back()->with('ok', 'Service supprimé.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'nom'  => 'required|string|max:255',
            'prix' => 'required|numeric|min:0',
        ]);
    }
}
