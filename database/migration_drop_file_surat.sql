-- OPTIONAL, IRREVERSIBLE — only run this once you're sure you don't need
-- any previously-uploaded leave attachments.
--
-- The leave-attachment feature has been fully removed from the app: no
-- code reads or writes `file_surat` anymore (confirmed — grepped the
-- entire codebase, zero references left). This migration drops the
-- now-unused column itself.
--
-- What this does NOT do: delete the actual files sitting in
-- /uploads/documents/ on disk. Those are untouched either way — this
-- only removes the database column that used to point to them.
--
-- Skip this entirely if you'd rather just leave the empty, unused column
-- in place — the app runs identically either way. This file exists only
-- for people who want the schema fully clean.

ALTER TABLE `leave_requests` DROP COLUMN `file_surat`;
