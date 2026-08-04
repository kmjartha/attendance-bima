<?php
require_once __DIR__ . '/../app/helpers/policy.php';

class ShiftSelectionTest
{
    public static function assertSame($expected, $actual, string $message): void
    {
        if ($expected !== $actual) {
            fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
            exit(1);
        }
    }
}

$rows = [
    [
        'id' => 1,
        'nama' => 'Shift Staff & Guru',
        'jam_masuk' => '07:30:00',
        'jam_keluar' => '12:00:00',
        'toleransi_menit' => 5,
        'hari_aktif' => 'Senin,Selasa,Rabu,Kamis,Jumat',
        'is_default' => 1,
    ],
    [
        'id' => 2,
        'nama' => 'Shift Staff Sabtu',
        'jam_masuk' => '09:00:00',
        'jam_keluar' => '12:00:00',
        'toleransi_menit' => 1,
        'hari_aktif' => 'Sabtu',
        'is_default' => 0,
    ],
];

$chosenSaturday = choose_shift_for_date($rows, '2026-08-01');
ShiftSelectionTest::assertSame(2, (int)($chosenSaturday['id'] ?? 0), 'Saturday-specific shift should be preferred over broad weekday shift');

$chosenFriday = choose_shift_for_date($rows, '2026-07-31');
ShiftSelectionTest::assertSame(1, (int)($chosenFriday['id'] ?? 0), 'Weekday shift should still be used on Friday');

echo "OK\n";
