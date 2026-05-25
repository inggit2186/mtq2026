<?php

namespace Tests\Unit;

use App\Models\JuknisSetting;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class JuknisSettingTest extends TestCase
{
    public function test_scheduled_access_enabled_uses_feature_name_inside_window_filter(): void
    {
        $setting = new JuknisSetting([
            'content' => [
                'registration_windows' => [
                    [
                        'label' => 'Masa Pendaftaran',
                        'start_at' => '11 Mei 2026 00:00',
                        'end_at' => '18 Mei 2026 23:59',
                        'official' => [
                            'participant_registration_open' => true,
                        ],
                        'panitia' => [],
                    ],
                ],
            ],
        ]);

        $this->assertTrue(
            $setting->scheduledAccessEnabled(
                'participant_registration_open',
                'official',
                Carbon::parse('2026-05-12 12:00', 'Asia/Bangkok')
            )
        );
    }
}
