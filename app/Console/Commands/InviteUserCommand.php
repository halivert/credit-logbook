<?php

namespace App\Console\Commands;

use App\Enums\Subscription;
use App\Invitations\Invitation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Registration is closed during the beta: the admin creates the account and
 * shares the link to choose a password.
 */
class InviteUserCommand extends Command
{
    protected $signature = 'user:invite
        {email : Their email, to sign in}
        {name : Their name}
        {--founder : Free as promised to the beta testers}
        {--again : A new link for someone already invited}';

    protected $description = 'Create an account and print a link to choose its password';

    public function handle(): int
    {
        $email = Str::lower($this->argument('email'));
        $user = User::query()->where('email', $email)->first();

        if ($user && ! $this->option('again')) {
            $this->components->error("Ya existe una cuenta con {$email}. Usa --again para generar otro enlace.");

            return self::FAILURE;
        }

        if (! $user) {
            $validator = Validator::make(
                ['email' => $email, 'name' => $this->argument('name')],
                ['email' => 'required|email|max:255', 'name' => 'required|string|max:255'],
            );

            if ($validator->fails()) {
                $this->components->error($validator->errors()->first());

                return self::FAILURE;
            }

            $user = $this->create($email, $this->argument('name'));
        }

        if ($this->option('founder')) {
            $user->forceFill(['subscription' => Subscription::Founder])->save();
        }

        $this->components->info("Cuenta de {$user->email} ({$user->subscription->value}).");
        $this->line('Comparte este enlace para que elija su contraseña. Vence en '.Invitation::DAYS.' días y sirve una vez:');
        $this->newLine();
        $this->line(Invitation::url($user));
        $this->newLine();

        return self::SUCCESS;
    }

    private function create(string $email, string $name): User
    {
        $user = User::query()->create([
            'name' => $name,
            'username' => $this->freeUsername(Str::before($email, '@')),
            'email' => $email,
            // Nobody knows it: they choose theirs with the link
            'password' => Str::random(64),
        ]);

        // The admin vouches for the address
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function freeUsername(string $base): string
    {
        $base = Str::slug($base, '.') ?: 'usuario';
        $username = $base;

        for ($i = 2; User::query()->where('username', $username)->exists(); $i++) {
            $username = "{$base}{$i}";
        }

        return $username;
    }
}
