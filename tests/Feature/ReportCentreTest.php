<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCentreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_reports(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_open_the_report_hub_and_catalogue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee(__('app.reports.title'), false)
            ->assertSee(__('app.reports.items.towers_master'), false)
            ->assertSee(__('app.reports.items.governance_audit'), false)
            ->assertSee(__('app.reports.items.executive_snapshot'), false)
            ->assertDontSee(__('app.reports.items.towers_map_tabular'), false)
            ->assertDontSee(__('app.reports.items.operators_tower_list'), false)
            ->assertDontSee(__('app.reports.items.operators_summary'), false)
            ->assertDontSee(__('app.reports.items.operators_frequencies'), false);
    }

    public function test_report_hub_lists_each_report_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $listed = \App\Reports\ReportCatalog::groupedFor($admin)->flatten();

        $this->assertSame($listed->count(), $listed->unique('key')->count());
        $this->assertSame($listed->count(), $listed->map->title()->unique()->count());
    }

    public function test_removed_duplicate_report_keys_redirect_to_the_canonical_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('reports.show', 'towers.map_tabular'))
            ->assertRedirect(route('reports.show', 'towers.master'));
    }

    public function test_admin_can_view_and_sort_the_master_register(): void
    {
        [$awdal, $sahil, $telesom, $somtel] = $this->fixtures();
        $this->tower('Awdal site', $awdal, $telesom);
        $this->tower('Sahil site', $sahil, $somtel);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('reports.show', ['report' => 'towers.master', 'sort' => 'name', 'dir' => 'asc']))
            ->assertOk()
            ->assertSee('Awdal site', false)
            ->assertSee('Sahil site', false)
            ->assertSee(__('app.reports.print'), false)
            ->assertSee(__('app.reports.excel'), false)
            ->assertSee(__('app.ministry_en'), false)
            ->assertSee('Jamhuuriyadda Somaliland', false)
            ->assertSee('Republic of Somaliland', false)
            ->assertSee('Wasaaradda Isgaadhsiinta iyo Teknoolojiyadda', false)
            ->assertSee('Waaxda Isgaadhsiinta', false)
            ->assertSee(__('app.reports.official_document'), false)
            ->assertSee(__('app.reports.no'), false)
            ->assertSee(__('app.map.health'), false)
            ->assertSee(__('app.towers.power_source'), false)
            ->assertSee(__('app.filter'), false)
            ->assertSee(__('app.reset'), false);
    }

    public function test_admin_can_download_excel(): void
    {
        [$awdal, , $telesom] = $this->fixtures();
        $this->tower('Awdal site', $awdal, $telesom);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('reports.excel', 'towers.master'))
            ->assertOk();

        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $response->getContent());
    }

    public function test_inspector_only_sees_scoped_reports_and_own_region(): void
    {
        [$awdal, $sahil, $telesom] = $this->fixtures();
        $this->tower('Awdal site', $awdal, $telesom);
        $this->tower('Sahil site', $sahil, $telesom);

        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $awdal->id,
        ]);

        $this->actingAs($inspector)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee(__('app.reports.items.towers_master'), false)
            ->assertDontSee(__('app.reports.items.governance_audit'), false)
            ->assertDontSee(__('app.reports.items.executive_snapshot'), false);

        $this->actingAs($inspector)
            ->get(route('reports.show', 'towers.master'))
            ->assertOk()
            ->assertSee('Awdal site', false)
            ->assertDontSee('Sahil site', false)
            ->assertDontSee(__('app.reports.excel'), false);

        $this->actingAs($inspector)
            ->get(route('reports.show', 'governance.audit'))
            ->assertNotFound();

        $this->actingAs($inspector)
            ->get(route('reports.excel', 'towers.master'))
            ->assertForbidden();
    }

    public function test_operations_manager_cannot_open_the_audit_report(): void
    {
        $ops = User::factory()->create(['role' => 'operations_manager']);

        $this->actingAs($ops)
            ->get(route('reports.show', 'governance.audit'))
            ->assertNotFound();

        $this->actingAs($ops)
            ->get(route('reports.show', 'executive.snapshot'))
            ->assertOk();
    }

    public function test_admin_can_open_every_catalogue_report(): void
    {
        [$awdal, $sahil, $telesom, $somtel] = $this->fixtures();
        $this->tower('Awdal site', $awdal, $telesom);
        $this->tower('Sahil site', $sahil, $somtel);
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (\App\Reports\ReportCatalog::all() as $report) {
            $this->actingAs($admin)
                ->get(route('reports.show', $report->key))
                ->assertOk();
        }
    }

    /**
     * @return array{0: Region, 1: Region, 2: Operator, 3: Operator}
     */
    private function fixtures(): array
    {
        $awdal = Region::query()->create(['name_en' => 'Awdal', 'name_so' => 'Awdal']);
        $sahil = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        $telesom = Operator::query()->create(['name' => 'Telesom', 'category' => 'telecom', 'color' => '#0F766E']);
        $somtel = Operator::query()->create(['name' => 'Somtel', 'category' => 'telecom', 'color' => '#1D4ED8']);

        return [$awdal, $sahil, $telesom, $somtel];
    }

    private function tower(string $name, Region $region, Operator $operator): Tower
    {
        return Tower::query()->create([
            'name' => $name,
            'latitude' => 9.56,
            'longitude' => 44.07,
            'region_id' => $region->id,
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 10000,
            'status' => 'active',
            'health_status' => 'unknown',
        ]);
    }
}
