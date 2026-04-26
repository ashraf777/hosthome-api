-- ============================================================
-- STEP 2: ASSIGN PERMISSIONS TO ROLES
-- Run AFTER 01_insert_permissions.sql.
-- Uses INSERT IGNORE so it is completely SAFE to re-run.
-- Works by ROLE NAME and PERMISSION NAME (not hardcoded IDs).
--
-- REQUIRED: Your roles table must have these 3 role names:
--   'Super Admin', 'Host Manager', 'Staff/Cleaner'
--
-- If your roles have different names, update the WHERE clauses below.
-- ============================================================

-- First, let's see what roles exist (run this SELECT to confirm names):
-- SELECT id, name FROM roles;

-- ============================================================
-- ROLE 1: Super Admin — gets ALL permissions
-- ============================================================
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT
    (SELECT id FROM roles WHERE name = 'Super Admin' LIMIT 1),
    p.id
FROM permissions p
WHERE (SELECT id FROM roles WHERE name = 'Super Admin' LIMIT 1) IS NOT NULL;

-- ============================================================
-- ROLE 2: Host Manager — operational permissions (no platform/role/user/plan admin)
-- ============================================================
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT
    (SELECT id FROM roles WHERE name = 'Host Manager' LIMIT 1),
    p.id
FROM permissions p
WHERE p.name IN (
    -- Property
    'property:view', 'property:create', 'property:update', 'property:delete',
    -- Property Owner
    'property-owner:view', 'property-owner:create', 'property-owner:update', 'property-owner:delete',
    -- Property Reference
    'property-reference:view', 'property-reference:create', 'property-reference:update', 'property-reference:delete',
    -- Room Type
    'room-type:view', 'room-type:create', 'room-type:update', 'room-type:delete',
    -- Room Type Photo
    'room-type-photo:view', 'room-type-photo:create', 'room-type-photo:update', 'room-type-photo:delete',
    -- Unit
    'unit:view', 'unit:create', 'unit:update', 'unit:delete',
    -- Amenity
    'amenity:view', 'amenity:create', 'amenity:update', 'amenity:delete',
    -- Booking
    'booking:view', 'booking:create', 'booking:update', 'booking:delete',
    -- Pricing
    'pricing-rule:view', 'pricing-rule:create', 'pricing-rule:update', 'pricing-rule:delete',
    'availability:view',
    -- Channel
    'channel:view', 'channel:create', 'channel:update', 'channel:delete',
    -- Tasks
    'task:view', 'task:create', 'task:update', 'task:delete',
    -- Messages
    'message:view', 'message:create',
    -- SEO
    'seo:manage',
    -- Role/User VIEW only (can see but not manage)
    'role:view', 'user:view'
)
AND (SELECT id FROM roles WHERE name = 'Host Manager' LIMIT 1) IS NOT NULL;

-- ============================================================
-- ROLE 3: Staff/Cleaner — read-only operational access
-- ============================================================
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT
    (SELECT id FROM roles WHERE name = 'Staff/Cleaner' LIMIT 1),
    p.id
FROM permissions p
WHERE p.name IN (
    -- View only
    'property:view',
    'booking:view',
    'task:view', 'task:update',   -- can update task status (e.g., mark completed)
    'message:view',
    'unit:view',
    'room-type:view'
)
AND (SELECT id FROM roles WHERE name = 'Staff/Cleaner' LIMIT 1) IS NOT NULL;

-- ============================================================
-- Verify: Show all role-permission assignments
-- ============================================================
SELECT
    r.name AS role_name,
    p.name AS permission_name
FROM role_permission rp
JOIN roles r ON r.id = rp.role_id
JOIN permissions p ON p.id = rp.permission_id
ORDER BY r.name, p.name;
