# Database setup

Run these on a fresh server in this exact order (each one creates or
selects the `absensi_sekolah` database itself, so you can just Import
each file in phpMyAdmin without needing to click into a database first):

1. `absensi_sekolah_fixed.sql`     — base schema + existing data
2. `migration_cuti_manual.sql`     — adds the "darurat" (mendadak/force majeure) leave type
3. `migration_leave_request_dates.sql` — adds per-date leave tracking (lets one
   leave request cover several non-consecutive dates)
4. `migration_jenis_karyawan.sql` — adds the employee type used by the
   employee create/edit forms

   For #4, first select the database used by the application in phpMyAdmin.
   The migration intentionally does not assume a database name, so it works
   with both local and production database names.

   After importing #3, also run these once via SSH (PHP scripts, not SQL —
   phpMyAdmin can't run these):
   ```
   php app-core/app/cron/backfill_leave_request_dates.php
   php app-core/app/cron/backfill_leave_attendance.php
   ```

If the database already exists and you're only adding a new feature,
you only need to run the migration file(s) you haven't run yet — each
one is safe to run more than once.

## Optional cleanup

- `migration_drop_file_surat.sql` — the leave-attachment upload feature
  was fully removed from the app. This drops the now-unused
  `file_surat` column. Not required (the app works fine leaving the
  empty column in place) and irreversible, so only run it if you want
  the schema fully clean.
