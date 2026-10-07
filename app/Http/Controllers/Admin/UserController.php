<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'lowercase', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('ok', 'Le compte a été créé.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'lowercase', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if ($request->user()->is($user) && $validated['role'] !== 'admin') {
            throw ValidationException::withMessages([
                'role' => 'Vous ne pouvez pas retirer votre propre rôle administrateur.',
            ]);
        }

        DB::transaction(function () use ($request, $user, $validated) {
            if ($user->isAdmin() && $user->is_active && $validated['role'] !== 'admin') {
                $this->ensureAnotherActiveAdminExists();
            }

            $user->name = $validated['name'];

            if ($user->email !== $validated['email']) {
                $user->email = $validated['email'];
                $user->email_verified_at = now();
            }

            $user->role = $validated['role'];

            if ($request->filled('password')) {
                $user->password = $validated['password'];
            }

            $user->save();
        });

        return redirect()->route('admin.users.index')->with('ok', 'Le compte a été modifié.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Vous ne pouvez pas désactiver votre propre compte depuis cette page.',
            ]);
        }

        DB::transaction(function () use ($user) {
            if ($user->isAdmin() && $user->is_active) {
                $this->ensureAnotherActiveAdminExists();
            }

            $user->is_active = ! $user->is_active;
            $user->save();
        });

        return redirect()->route('admin.users.index')->with(
            'ok',
            $user->is_active ? 'Le compte a été réactivé.' : 'Le compte a été désactivé.'
        );
    }

    private function ensureAnotherActiveAdminExists(): void
    {
        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->lockForUpdate()
            ->get();

        if ($admins->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'Il doit rester au moins un administrateur actif.',
            ]);
        }
    }
}
