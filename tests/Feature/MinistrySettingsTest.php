<?php

namespace Tests\Feature;

use App\Models\MinistrySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinistrySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_ministry_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $settings = MinistrySetting::current();

        $this->actingAs($admin)
            ->get(route('settings.ministry.edit'))
            ->assertOk()
            ->assertSee(__('app.settings.ministry_title'), false);

        $this->actingAs($admin)
            ->put(route('settings.ministry.update', $settings), [
                'approval_director_name' => 'Eng. Fatima Ali',
                'approval_director_title_so' => 'Agaasimaha Waaxda Isgaadhsiinta',
                'approval_director_title_en' => 'Director of Communication Department',
            ])
            ->assertRedirect(route('settings.ministry.edit'));

        $this->assertDatabaseHas('ministry_settings', [
            'id' => $settings->id,
            'approval_director_name' => 'Eng. Fatima Ali',
        ]);
    }

    public function test_inspector_cannot_access_ministry_settings(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);

        $this->actingAs($inspector)
            ->get(route('settings.ministry.edit'))
            ->assertForbidden();
    }
}
