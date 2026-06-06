<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Stage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji metadata template tanaman: thumbnail, category, growth_days.
 */
class CropTemplateMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_crop_templates_expose_metadata_fields(): void
    {
        Crop::factory()->create([
            'name' => 'Cabai',
            'category' => 'Hortikultura',
            'image_url' => 'https://example.com/cabai.jpg',
        ]);

        $this->getJson('/api/crop-templates')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'description', 'thumbnail', 'category', 'growth_days'],
                ],
            ])
            ->assertJsonFragment(['category' => 'Hortikultura']);
    }

    public function test_growth_days_equals_sum_of_stage_duration_days(): void
    {
        $crop = Crop::factory()->create(['name' => 'Padi', 'category' => 'Pangan']);

        Stage::factory()->create(['crop_id' => $crop->id, 'order' => 1, 'duration_days' => 7]);
        Stage::factory()->create(['crop_id' => $crop->id, 'order' => 2, 'duration_days' => 14]);
        Stage::factory()->create(['crop_id' => $crop->id, 'order' => 3, 'duration_days' => 5]);

        $this->getJson('/api/crop-templates')
            ->assertOk()
            ->assertJsonPath('data.0.growth_days', 26);
    }

    public function test_crop_without_stages_or_metadata_returns_nulls(): void
    {
        // Crop tanpa stage, tanpa category, tanpa image_url → field null, tetap 200.
        Crop::factory()->create([
            'name' => 'Kosong',
            'category' => null,
            'image_url' => null,
        ]);

        $this->getJson('/api/crop-templates')
            ->assertOk()
            ->assertJsonPath('data.0.category', null)
            ->assertJsonPath('data.0.thumbnail', null)
            ->assertJsonPath('data.0.growth_days', null);
    }
}
