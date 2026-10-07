<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin {email? : E-mail du compte à promouvoir ou du nouvel administrateur}';

    protected $description = 'Créer un compte administrateur ou promouvoir un compte existant';

    public function handle(): int
    {
        $email = strtolower((string) ($this->argument('email') ?: $this->ask('Adresse e-mail')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Veuillez saisir une adresse e-mail valide.');

            return self::INVALID;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->isAdmin()) {
                $this->info('Ce compte est déjà administrateur.');

                return self::SUCCESS;
            }

            if (! $this->confirm("Promouvoir {$user->email} au rôle administrateur ?")) {
                return self::FAILURE;
            }

            $user->update(['role' => 'admin']);
            $this->info("{$user->email} est maintenant administrateur.");

            return self::SUCCESS;
        }

        $name = trim($this->ask('Nom de l’administrateur'));
        $password = $this->secret('Mot de passe (12 caractères minimum)');
        $passwordConfirmation = $this->secret('Confirmer le mot de passe');

        try {
            validator(
                ['password' => $password, 'password_confirmation' => $passwordConfirmation],
                ['password' => ['required', 'confirmed', Password::min(12)]]
            )->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->errors()['password'] as $error) {
                $this->error($error);
            }

            return self::INVALID;
        }

        if ($name === '') {
            $this->error('Le nom est obligatoire.');

            return self::INVALID;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Le compte administrateur {$email} a été créé.");

        return self::SUCCESS;
    }
}
