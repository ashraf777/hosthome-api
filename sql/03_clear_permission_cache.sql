-- ============================================================
-- STEP 3: CLEAR STALE PERMISSION CACHE
-- Run AFTER updating role permissions (step 1 & 2), or any time
-- you change permissions in the Access Control UI and the API
-- is still returning old (wrong) permission results.
--
-- Your app uses CACHE_STORE=database, so the cache is stored
-- in the `cache` table. This query safely deletes ONLY the
-- permission cache entries, leaving all other cache intact.
-- ============================================================

-- Delete all cached permission entries for all users
DELETE FROM `cache`
WHERE `key` LIKE '%user.%.permissions%'
   OR `key` LIKE '%permissions%';

-- Also clear the entire cache table if you want a full reset
-- (Optional — uncomment if the above isn't enough)
-- TRUNCATE TABLE `cache`;

-- Verify: should return 0 rows after deletion
SELECT * FROM `cache` WHERE `key` LIKE '%permission%';
