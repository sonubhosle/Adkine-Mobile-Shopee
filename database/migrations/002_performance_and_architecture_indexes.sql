-- =============================================================================
-- Migration: 002_performance_and_architecture_indexes.sql
-- Purpose: Optimize query paths for Catalog, Admin Lists, Customer Dashboards & Tech Workbench
-- Engine: MySQL 8.0 / MariaDB 10.4 InnoDB
-- Reversible: Yes (See DOWN section)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- UP MIGRATION
-- -----------------------------------------------------------------------------

-- 1. Products: Admin catalog created_at sort
CREATE INDEX IF NOT EXISTS idx_prod_created_at ON products (created_at DESC);

-- 2. Users: Role-based listing & customer sorting
CREATE INDEX IF NOT EXISTS idx_users_role_created ON users (role_id, created_at DESC);

-- 3. Orders: Customer tenant order history sort
CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders (user_id, id DESC);

-- 4. Repairs: Customer tenant repair history & Technician priority sort
CREATE INDEX IF NOT EXISTS idx_repairs_user_id ON repairs (user_id, id DESC);
CREATE INDEX IF NOT EXISTS idx_repairs_tech_prio_id ON repairs (assigned_technician_id, priority, id DESC);

-- -----------------------------------------------------------------------------
-- DOWN MIGRATION (ROLLBACK INSTRUCTIONS)
-- -----------------------------------------------------------------------------
-- DROP INDEX idx_prod_created_at ON products;
-- DROP INDEX idx_users_role_created ON users;
-- DROP INDEX idx_orders_user_id ON orders;
-- DROP INDEX idx_repairs_user_id ON repairs;
-- DROP INDEX idx_repairs_tech_prio_id ON repairs;

