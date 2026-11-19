<?php

namespace Tests\Unit;

use App\Models\UserDetail;
use App\Support\PageLook;
use Tests\TestCase;

class PageLookTest extends TestCase
{
    public function test_new_artist_defaults_produce_custom_midnight_appearance(): void
    {
        $state = PageLook::stateFromUserDetail(new UserDetail(PageLook::NEW_ARTIST_DEFAULTS));

        $this->assertSame('custom', $state['mode']);
        $this->assertSame(PageLook::DEFAULT_SCHEME, $state['scheme']);
        $this->assertSame('#FFFFFF', $state['c']['box']);
        $this->assertSame('solid', $state['c']['effect']);
        $this->assertSame('auto', $state['c']['title']);
        $this->assertSame('auto', $state['c']['text']);
        $this->assertSame('auto', $state['c']['btn']);
        $this->assertSame('l', $state['c']['radius']);
    }

    public function test_standard_button_text_uses_scheme_contrast_not_custom_button_setting(): void
    {
        $state = [
            'mode' => 'standard',
            'scheme' => '#FFFFFF,#FFFFFF,#FFFFFF',
            'c' => [
                'btn' => 'white',
                'btnC' => '#FFFFFF',
            ],
        ];

        $this->assertSame('#111111', PageLook::buttonTextColorFromState($state));
    }

    public function test_custom_button_text_setting_is_preserved_in_custom_mode(): void
    {
        $state = [
            'mode' => 'custom',
            'scheme' => '#1A1A2E,#3A3A5C,#ECEBF3',
            'c' => [
                'box' => '#FFFFFF',
                'effect' => 'solid',
                'op' => 100,
                'btn' => 'black',
                'radius' => 'l',
            ],
        ];

        $this->assertSame('#111111', PageLook::buttonTextColorFromState($state));
    }
}
