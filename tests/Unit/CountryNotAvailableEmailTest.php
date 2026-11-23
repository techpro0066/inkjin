<?php

namespace Tests\Unit;

use Tests\TestCase;

class CountryNotAvailableEmailTest extends TestCase
{
    public function test_template_renders_the_new_country_not_available_layout(): void
    {
        $html = view('emails.country-not-available', [
            'countryName' => 'India',
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('India', $html);
        $this->assertStringContainsString('No account has been created', $html);
    }
}
