<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteGuidelinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_the_guidelines_window(): void
    {
        $this->get(route('guidelines'))
            ->assertOk()
            ->assertSee('Guidelines', false)
            ->assertSee('Formal request letter', false)
            ->assertSee('Location map', false)
            ->assertSee('Site layout plan', false)
            ->assertSee('Planned radio apparatus specifications', false)
            ->assertSee('Declaration of compliance', false)
            ->assertSee('ICNIRP', false)
            ->assertSee('Download official document', false)
            ->assertDontSee('Back to sign in', false)
            ->assertDontSee(route('login'), false)
            ->assertDontSee('Landowner or property approval', false)
            ->assertDontSee('18 × 24', false);
    }

    public function test_guidelines_page_has_no_somali_toggle(): void
    {
        $this->withSession(['locale' => 'so'])
            ->get(route('guidelines'))
            ->assertOk()
            ->assertSee('Site registration guidelines', false)
            ->assertDontSee('Hagaha diiwaangelinta goobta', false)
            ->assertDontSee('name="locale"', false);
    }

    public function test_login_page_links_to_guidelines(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('guidelines'), false)
            ->assertSee(__('app.guidelines.open', [], 'en'), false);
    }

    public function test_guidelines_pdf_is_the_official_document(): void
    {
        $path = \App\Support\SiteRegistrationGuidelines::officialPdfPath();
        $this->assertNotNull($path);
        $this->assertStringStartsWith('%PDF', (string) file_get_contents($path, false, null, 0, 8));

        $response = $this->get(route('guidelines.pdf'));

        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString(
            \App\Support\SiteRegistrationGuidelines::downloadFilename(),
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_staff_can_open_guidelines_from_help(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('guidelines'))
            ->assertOk()
            ->assertSee(route('dashboard'), false)
            ->assertDontSee('Back to sign in', false);

        $this->actingAs($admin)
            ->get(route('help'))
            ->assertOk()
            ->assertSee(route('guidelines'), false)
            ->assertSee(route('apply.create'), false);
    }
}
