# Database setup

Run these on a fresh server in this exact order (each one creates or
selects the `absensi_sekolah` database itself, so you can just Import
each file in phpMyAdmin without needing to click into a database first):

1. `absensi_sekolah_fixed.sql`     — base schema + existing data
2. `migration_cuti_manual.sql`     — adds the "darurat" (mendadak/force majeure) leave type
3. `migration_leave_request_dates.sql` — adds per-date leave tracking (lets one
   leave request cover several non-consecutive dates)

If the database already exists and you're only adding a new feature,
you only need to run the migration file(s) you haven't run yet — each
one is safe to run more than once.
