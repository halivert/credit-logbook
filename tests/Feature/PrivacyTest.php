<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    #[Test]
    public function the_privacy_notice_is_public_and_names_who_answers(): void
    {
        config(['privacy.owner' => 'Ana Pérez', 'privacy.email' => 'privacidad@example.com']);

        $this->get(route('privacy'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Privacy')
                ->where('owner', 'Ana Pérez')
                ->where('email', 'privacidad@example.com'));
    }

    #[Test]
    public function the_terms_of_use_are_public(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Terms'));
    }
}
