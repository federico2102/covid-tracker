<?php

namespace Tests\Support;

use App\Models\InfectionReport;

class InfectionReportTestHelper
{
    public static function reportPositiveTest($user, $testDate = null, $proof = null)
    {
        return $user->post(route('infectionReports.store'), [
            'test_date' => $testDate ?? now()->format('Y-m-d'),
            'proof' => $proof,
        ]);
    }

    public static function reportNegativeTest($user, $proof = null)
    {
        return $user->post(route('infectionReports.negative'), [
            'proof' => $proof,
        ]);
    }

    public static function createInfectionReport($user, $testDate = null, $isActive = true)
    {
        return InfectionReport::create([
            'user_id' => $user->id,
            'test_date' => $testDate ?? now()->format('Y-m-d'),
            'is_active' => $isActive,
        ]);
    }

    public static function resetInfectedStatus($command)
    {
        return $command->artisan('checkin:auto-reset-infected-status')->assertExitCode(0);
    }
}
