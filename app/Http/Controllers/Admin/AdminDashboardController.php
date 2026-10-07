<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'userCount' => User::where('is_active', true)->count(),
            'invoiceCount' => Facture::count(),
            'monthlyTotal' => Facture::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total'),
            'recentFactures' => Facture::with('user')->latest()->limit(8)->get(),
        ]);
    }
}
