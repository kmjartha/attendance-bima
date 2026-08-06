<?php
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/LeaveRequest.php';

use App\Models\LeaveRequest;

class ApprovedLeaveGuardTest
{
    public static function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            fwrite(STDERR, "FAIL: {$message}\n");
            exit(1);
        }
    }
}

$leave = [
    'tanggal_mulai' => '2026-08-08',
    'tanggal_selesai' => '2026-08-10',
];

ApprovedLeaveGuardTest::assertTrue(
    LeaveRequest::isDateWithinLeavePeriod('2026-08-09', $leave),
    'Approved leave should cover the requested attendance date.'
);

ApprovedLeaveGuardTest::assertTrue(
    !LeaveRequest::isDateWithinLeavePeriod('2026-08-11', $leave),
    'Approved leave should not cover dates after the leave range.'
);

echo "OK\n";
