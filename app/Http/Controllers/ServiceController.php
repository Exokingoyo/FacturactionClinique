<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['nom', 'prix'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $search = trim($filters['search'] ?? '');
        $sort = $filters['sort'] ?? 'nom';
        $direction = $filters['direction'] ?? 'asc';

        $services = Service::query()
            ->when($search !== '', fn ($query) => $query->where('nom', 'like', "%{$search}%"))
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return view('services.index', compact('services', 'filters', 'sort', 'direction'));
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
