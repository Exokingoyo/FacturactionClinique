<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FactureController extends Controller
{
    public function index()
    {
        $factures = Facture::with('user')
            ->when(! request()->user()->isAdmin(), fn ($query) => $query->where('user_id', request()->user()->id))
            ->latest()
            ->paginate(20);
        return view('factures.index', compact('factures'));
    }

    public function create()
    {
        return view('factures.create', ['services' => Service::orderBy('nom')->get()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_nom'       => 'required|string|max:255',
            'patient_telephone' => 'nullable|string|max:50',
            'services'          => 'required|array|min:1',
            'services.*'        => 'exists:services,id',
            'quantites'         => 'array',
        ], ['services.required' => 'Choisissez au moins un examen.']);

        $facture = DB::transaction(function () use ($request) {
            $facture = Facture::create([
                'patient_nom'       => $request->patient_nom,
                'patient_telephone' => $request->patient_telephone,
                'user_id'           => $request->user()->id,
            ]);

            $total = 0;
            foreach (Service::whereIn('id', $request->services)->get() as $s) {
                $qte = max(1, (int) ($request->quantites[$s->id] ?? 1));
                // on copie nom et prix : la facture ne change pas si le tarif change plus tard
                $facture->lignes()->create([
                    'designation' => $s->nom,
                    'prix'        => $s->prix,
                    'quantite'    => $qte,
                ]);
                $total += $s->prix * $qte;
            }

            $facture->update([
                'numero' => 'F-' . date('ymHis') . '-' . strtoupper(Str::random(5)),
                // 'numero' => 'F-' . date('Y') . '-' . str_pad($facture->id, 5, '0', STR_PAD_LEFT),
                'total'  => $total,
            ]);

            return $facture;
        });

        return redirect()->route('factures.show', $facture);
    }

    public function show(Facture $facture)
    {
        $this->authorizeView($facture);
        $facture->load('lignes', 'user');
        return view('factures.show', compact('facture'));
    }

    public function pdf(Facture $facture)
    {
        $this->authorizeView($facture);
        $facture->load('lignes', 'user');
        return Pdf::loadView('factures.pdf', compact('facture'))
            ->stream($facture->numero . '.pdf');
    }

    public function edit(Facture $facture)
    {
        $facture->load('lignes', 'user');

        return view('factures.edit', [
            'facture' => $facture,
            'services' => Service::orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, Facture $facture)
    {
        $validated = $request->validate([
            'patient_nom' => ['required', 'string', 'max:255'],
            'patient_telephone' => ['nullable', 'string', 'max:50'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.id' => [
                'nullable',
                'integer',
                Rule::exists('facture_lignes', 'id')->where('facture_id', $facture->id),
            ],
            'lignes.*.designation' => ['required', 'string', 'max:255'],
            'lignes.*.prix' => ['required', 'numeric', 'min:0'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
        ], [
            'lignes.required' => 'Une facture doit contenir au moins une ligne.',
            'lignes.min' => 'Une facture doit contenir au moins une ligne.',
        ]);

        DB::transaction(function () use ($facture, $validated) {
            $facture->update([
                'patient_nom' => $validated['patient_nom'],
                'patient_telephone' => $validated['patient_telephone'] ?? null,
            ]);

            $lineIds = [];
            $total = 0;

            foreach ($validated['lignes'] as $lineData) {
                if (! empty($lineData['id'])) {
                    $line = $facture->lignes()->whereKey($lineData['id'])->firstOrFail();
                    $line->update($lineData);
                } else {
                    $line = $facture->lignes()->create($lineData);
                }

                $lineIds[] = $line->id;
                $total += (float) $lineData['prix'] * (int) $lineData['quantite'];
            }

            $facture->lignes()->whereNotIn('id', $lineIds)->delete();
            $facture->update(['total' => $total]);
        });

        return redirect()->route('factures.show', $facture)->with('ok', 'La facture a été modifiée.');
    }

    private function authorizeView(Facture $facture): void
    {
        abort_unless(
            request()->user()->isAdmin() || $facture->user_id === request()->user()->id,
            403
        );
    }
}
