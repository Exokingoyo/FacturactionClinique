<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\FactureLigne;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use JsonException;

class FactureController extends Controller
{
    public function index()
    {
        $factures = Facture::with('user')
            ->when(! request()->user()->isAdmin(), fn ($query) => $query->where('user_id', request()->user()->id))
            ->latest()
            ->paginate(10);

        return view('factures.index', compact('factures'));
    }

    public function create()
    {
        $services = Service::orderBy('nom')->get();
        $catalog = $services->map(fn (Service $service) => [
            'id' => $service->id,
            'nom' => $service->nom,
            'prix' => (string) $service->prix,
        ])->values()->all();

        return view('factures.create', [
            'services' => $services,
            'catalogProof' => Crypt::encryptString(json_encode($catalog, JSON_THROW_ON_ERROR)),
        ]);
    }

    public function synchronizeOffline(Request $request)
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'patient_nom' => ['required', 'string', 'max:255'],
            'patient_telephone' => ['nullable', 'string', 'max:50'],
            'catalog_proof' => ['required', 'string'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.service_id' => ['required', 'integer', 'distinct'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $catalog = json_decode(
                Crypt::decryptString($validated['catalog_proof']),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (DecryptException|JsonException $exception) {
            return response()->json([
                'message' => 'Le catalogue hors ligne a expiré. Rechargez la page lorsque vous êtes connecté à Internet.',
            ], 422);
        }

        abort_unless(is_array($catalog), 422, 'Le catalogue hors ligne est invalide.');

        $services = collect($catalog)->keyBy(fn (array $service) => (string) $service['id']);
        $total = 0;
        $lines = [];

        foreach ($validated['lignes'] as $line) {
            $service = $services->get((string) $line['service_id']);

            if (! $service || ! isset($service['nom'], $service['prix'])) {
                return response()->json([
                    'message' => 'Une prestation n’appartient pas au catalogue chargé. Reconnectez-vous et rechargez la page.',
                ], 422);
            }

            $quantity = (int) $line['quantite'];
            $price = (string) $service['prix'];
            $total += (float) $price * $quantity;
            $lines[] = [
                'designation' => $service['nom'],
                'prix' => $price,
                'quantite' => $quantity,
            ];
        }

        $invoice = DB::transaction(function () use ($request, $validated, $lines, $total) {
            $invoice = Facture::firstOrCreate(
                ['offline_uuid' => $validated['idempotency_key']],
                [
                    'numero' => 'F-'.date('ymHis').'-'.strtoupper(Str::random(5)),
                    'patient_nom' => $validated['patient_nom'],
                    'patient_telephone' => $validated['patient_telephone'] ?? null,
                    'total' => $total,
                    'user_id' => $request->user()->id,
                ]
            );

            abort_unless($invoice->user_id === $request->user()->id, 409);

            if ($invoice->wasRecentlyCreated) {
                $invoice->lignes()->createMany($lines);
            } else {
                $existingLines = $invoice->lignes()->get()->map(fn (FactureLigne $line) => [
                    'designation' => $line->designation,
                    'prix' => number_format((float) $line->prix, 2, '.', ''),
                    'quantite' => (int) $line->quantite,
                ])->all();
                $requestedLines = array_map(fn (array $line) => [
                    'designation' => $line['designation'],
                    'prix' => number_format((float) $line['prix'], 2, '.', ''),
                    'quantite' => (int) $line['quantite'],
                ], $lines);

                abort_unless(
                    $invoice->patient_nom === $validated['patient_nom']
                    && $invoice->patient_telephone === ($validated['patient_telephone'] ?? null)
                    && $existingLines === $requestedLines,
                    409
                );
            }

            return $invoice;
        });

        return response()->json([
            'message' => 'Facture synchronisée.',
            'redirect_url' => route('factures.show', $invoice),
            'numero' => $invoice->numero,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_nom' => 'required|string|max:255',
            'patient_telephone' => 'nullable|string|max:50',
            'services' => 'required|array|min:1',
            'services.*' => 'exists:services,id',
            'quantites' => 'array',
        ], ['services.required' => 'Choisissez au moins un examen.']);

        $facture = DB::transaction(function () use ($request) {
            $facture = Facture::create([
                'patient_nom' => $request->patient_nom,
                'patient_telephone' => $request->patient_telephone,
                'user_id' => $request->user()->id,
            ]);

            $total = 0;
            foreach (Service::whereIn('id', $request->services)->get() as $s) {
                $qte = max(1, (int) ($request->quantites[$s->id] ?? 1));
                // on copie nom et prix : la facture ne change pas si le tarif change plus tard
                $facture->lignes()->create([
                    'designation' => $s->nom,
                    'prix' => $s->prix,
                    'quantite' => $qte,
                ]);
                $total += $s->prix * $qte;
            }

            $facture->update([
                'numero' => 'F-'.date('ymHis').'-'.strtoupper(Str::random(5)),
                'total' => $total,
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
        $paperHeight = 260 + $facture->lignes->sum(
            fn (FactureLigne $ligne) => 46 + max(0, ceil(mb_strlen($ligne->designation) / 24) - 1) * 12
        );

        return Pdf::loadView('factures.pdf', compact('facture'))
            ->setPaper([0, 0, 226.77, $paperHeight])
            ->stream($facture->numero.'.pdf');
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
