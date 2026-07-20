<?php

namespace Tests\Unit;

use App\Support\SocialLinks;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    public function test_accepts_valid_platform_urls(): void
    {
        $result = SocialLinks::normalize([
            'instagram' => 'https://www.instagram.com/inkjin',
            'tiktok' => 'https://www.tiktok.com/@inkjin',
            'youtube' => 'https://www.youtube.com/@inkjin',
            'facebook' => 'https://www.facebook.com/inkjin',
            'website' => 'https://inkjin.com',
        ]);

        $this->assertSame('https://www.instagram.com/inkjin', $result['instagram']);
        $this->assertSame('https://www.tiktok.com/@inkjin', $result['tiktok']);
        $this->assertSame('https://www.youtube.com/@inkjin', $result['youtube']);
        $this->assertSame('https://www.facebook.com/inkjin', $result['facebook']);
        $this->assertSame('https://inkjin.com', $result['website']);
    }

    public function test_normalizes_handles_for_supported_platforms(): void
    {
        $result = SocialLinks::normalize([
            'instagram' => '@inkjin',
            'tiktok' => 'inkjin',
            'youtube' => '@inkjin',
        ]);

        $this->assertSame('https://www.instagram.com/inkjin', $result['instagram']);
        $this->assertSame('https://www.tiktok.com/@inkjin', $result['tiktok']);
        $this->assertSame('https://www.youtube.com/@inkjin', $result['youtube']);
    }

    public function test_rejects_wrong_platform_url(): void
    {
        try {
            SocialLinks::normalize([
                'instagram' => 'https://www.tiktok.com/@inkjin',
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('social_links.instagram', $e->errors());
        }
    }

    public function test_rejects_facebook_handle_without_url(): void
    {
        try {
            SocialLinks::normalize([
                'facebook' => 'inkjin',
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('social_links.facebook', $e->errors());
        }
    }
}
