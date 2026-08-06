<?php

namespace Tests\Unit;

use App\Support\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_falls_back_to_defaults(): void
    {
        config(['jiwa.min_investment' => 75]);

        $this->assertEquals(75, PlatformSettings::config('jiwa.min_investment'));
    }

    public function test_db_setting_overrides_config(): void
    {
        config(['jiwa.min_investment' => 75]);
        PlatformSettings::set('min_investment', 120, 'investments');

        $this->assertEquals(120, PlatformSettings::config('jiwa.min_investment'));
    }

    public function test_types_are_cast(): void
    {
        PlatformSettings::set('float', 12.5);
        PlatformSettings::set('int', 12);
        PlatformSettings::set('bool_true', true);
        PlatformSettings::set('array', ['a' => 1, 'b' => 2]);

        $all = PlatformSettings::all();

        $this->assertSame(12.5, $all->get('float'));
        $this->assertSame(12, $all->get('int'));
        $this->assertTrue($all->get('bool_true'));
        $this->assertSame(['a' => 1, 'b' => 2], $all->get('array'));
    }

    public function test_update_overrides_existing_value(): void
    {
        PlatformSettings::set('min_withdrawal', 20, 'withdrawals');
        PlatformSettings::set('min_withdrawal', 50, 'withdrawals');

        $this->assertEquals(50, PlatformSettings::config('jiwa.min_withdrawal'));
    }
}
