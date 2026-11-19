<?php

namespace Tests\Unit;

use App\Models\ArtistDesign;
use Tests\TestCase;

class ArtistDesignImageDisplayTest extends TestCase
{
    public function test_whole_design_is_the_default_and_uses_the_original_image(): void
    {
        $design = new ArtistDesign([
            'image' => 'uploads/artist-designs/crop.jpg',
            'original_image' => 'uploads/artist-designs-originals/source.png',
        ]);

        $this->assertSame('whole', $design->image_display_mode);
        $this->assertSame('uploads/artist-designs-originals/source.png', $design->display_image);
        $this->assertSame('contain', $design->display_image_fit);
    }

    public function test_crop_to_fill_uses_the_saved_crop_and_cover_fit(): void
    {
        $design = new ArtistDesign([
            'image' => 'uploads/artist-designs/crop.jpg',
            'original_image' => 'uploads/artist-designs-originals/source.png',
            'image_display_mode' => 'crop',
        ]);

        $this->assertSame('uploads/artist-designs/crop.jpg', $design->display_image);
        $this->assertSame('cover', $design->display_image_fit);
    }

    public function test_whole_design_falls_back_to_image_when_original_is_unavailable(): void
    {
        $design = new ArtistDesign([
            'image' => 'uploads/artist-designs/legacy.jpg',
        ]);

        $this->assertSame('uploads/artist-designs/legacy.jpg', $design->display_image);
        $this->assertSame('contain', $design->display_image_fit);
    }
}
