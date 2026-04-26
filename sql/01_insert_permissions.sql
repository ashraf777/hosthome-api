-- ============================================================
-- STEP 1: INSERT ALL REQUIRED PERMISSIONS
-- Run this in phpMyAdmin on the 'hosthome' database.
-- Uses INSERT IGNORE so it is completely SAFE to re-run.
-- It will skip any permission names that already exist.
-- ============================================================

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES

-- Platform / Super Admin
('platform:manage',          'web', NOW(), NOW()),

-- Plan Management (Super Admin only)
('plan:view',                'web', NOW(), NOW()),
('plan:create',              'web', NOW(), NOW()),
('plan:update',              'web', NOW(), NOW()),
('plan:delete',              'web', NOW(), NOW()),

-- Role Management
('role:view',                'web', NOW(), NOW()),
('role:create',              'web', NOW(), NOW()),
('role:update',              'web', NOW(), NOW()),
('role:assign-permission',   'web', NOW(), NOW()),

-- User Management
('user:view',                'web', NOW(), NOW()),
('user:create',              'web', NOW(), NOW()),
('user:update',              'web', NOW(), NOW()),
('user:assign-role',         'web', NOW(), NOW()),

-- Property Management
('property:view',            'web', NOW(), NOW()),
('property:create',          'web', NOW(), NOW()),
('property:update',          'web', NOW(), NOW()),
('property:delete',          'web', NOW(), NOW()),

-- Property Owner Management
('property-owner:view',      'web', NOW(), NOW()),
('property-owner:create',    'web', NOW(), NOW()),
('property-owner:update',    'web', NOW(), NOW()),
('property-owner:delete',    'web', NOW(), NOW()),

-- Property Reference Management
('property-reference:view',  'web', NOW(), NOW()),
('property-reference:create','web', NOW(), NOW()),
('property-reference:update','web', NOW(), NOW()),
('property-reference:delete','web', NOW(), NOW()),

-- Room Type Management
('room-type:view',           'web', NOW(), NOW()),
('room-type:create',         'web', NOW(), NOW()),
('room-type:update',         'web', NOW(), NOW()),
('room-type:delete',         'web', NOW(), NOW()),

-- Room Type Photo Management
('room-type-photo:view',     'web', NOW(), NOW()),
('room-type-photo:create',   'web', NOW(), NOW()),
('room-type-photo:update',   'web', NOW(), NOW()),
('room-type-photo:delete',   'web', NOW(), NOW()),

-- Unit Management
('unit:view',                'web', NOW(), NOW()),
('unit:create',              'web', NOW(), NOW()),
('unit:update',              'web', NOW(), NOW()),
('unit:delete',              'web', NOW(), NOW()),

-- Amenity Management
('amenity:view',             'web', NOW(), NOW()),
('amenity:create',           'web', NOW(), NOW()),
('amenity:update',           'web', NOW(), NOW()),
('amenity:delete',           'web', NOW(), NOW()),

-- Booking Management
('booking:view',             'web', NOW(), NOW()),
('booking:create',           'web', NOW(), NOW()),
('booking:update',           'web', NOW(), NOW()),
('booking:delete',           'web', NOW(), NOW()),

-- Pricing & Availability
('pricing-rule:view',        'web', NOW(), NOW()),
('pricing-rule:create',      'web', NOW(), NOW()),
('pricing-rule:update',      'web', NOW(), NOW()),
('pricing-rule:delete',      'web', NOW(), NOW()),
('availability:view',        'web', NOW(), NOW()),

-- Channel Management
('channel:view',             'web', NOW(), NOW()),
('channel:create',           'web', NOW(), NOW()),
('channel:update',           'web', NOW(), NOW()),
('channel:delete',           'web', NOW(), NOW()),

-- Task Management
('task:view',                'web', NOW(), NOW()),
('task:create',              'web', NOW(), NOW()),
('task:update',              'web', NOW(), NOW()),
('task:delete',              'web', NOW(), NOW()),

-- Message / Inbox
('message:view',             'web', NOW(), NOW()),
('message:create',           'web', NOW(), NOW()),

-- SEO Metadata
('seo:manage',               'web', NOW(), NOW()),

-- Hosting Company (Super Admin)
('hosting-company:view',     'web', NOW(), NOW()),
('hosting-company:create',   'web', NOW(), NOW()),
('hosting-company:update',   'web', NOW(), NOW()),
('hosting-company:delete',   'web', NOW(), NOW());

-- Verify: show all inserted permissions
SELECT id, name FROM `permissions` ORDER BY name;
