-- ====================================================================
-- ResourceHub PostgreSQL Relational Database Schema
-- Local equipment loan and maintenance management system
-- ====================================================================

-- Drop existing tables in reverse dependency order if resetting
DROP VIEW IF EXISTS loan_events CASCADE;
DROP TABLE IF EXISTS maintenance CASCADE;
DROP TABLE IF EXISTS loan_history CASCADE;
DROP TABLE IF EXISTS loans CASCADE;
DROP TABLE IF EXISTS equipment CASCADE;
DROP TABLE IF EXISTS locations CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- 1. Users Table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('administrator', 'coordinator', 'staff')),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_is_active ON users(is_active);

-- 2. Categories Table
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 3. Locations Table
CREATE TABLE locations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    building VARCHAR(100),
    room VARCHAR(50),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 4. Equipment Table
CREATE TABLE equipment (
    id SERIAL PRIMARY KEY,
    asset_tag VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_id INT NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
    location_id INT NOT NULL REFERENCES locations(id) ON DELETE RESTRICT,
    model VARCHAR(100),
    serial_number VARCHAR(100),
    condition VARCHAR(20) NOT NULL DEFAULT 'good' CHECK (condition IN ('new', 'good', 'fair', 'damaged')),
    status VARCHAR(20) NOT NULL DEFAULT 'available' CHECK (status IN ('available', 'maintenance', 'retired')),
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_equipment_asset_tag ON equipment(asset_tag);
CREATE INDEX idx_equipment_category ON equipment(category_id);
CREATE INDEX idx_equipment_location ON equipment(location_id);
CREATE INDEX idx_equipment_status ON equipment(status);
CREATE INDEX idx_equipment_condition ON equipment(condition);

-- 5. Loans Table
CREATE TABLE loans (
    id SERIAL PRIMARY KEY,
    asset_id INT NOT NULL REFERENCES equipment(id) ON DELETE RESTRICT,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'checked_out', 'returned', 'rejected', 'cancelled')),
    purpose TEXT NOT NULL,
    rejection_reason TEXT,
    approved_by INT REFERENCES users(id) ON DELETE SET NULL,
    approved_at TIMESTAMP WITH TIME ZONE,
    checked_out_by INT REFERENCES users(id) ON DELETE SET NULL,
    checked_out_at TIMESTAMP WITH TIME ZONE,
    returned_by INT REFERENCES users(id) ON DELETE SET NULL,
    returned_at TIMESTAMP WITH TIME ZONE,
    return_condition VARCHAR(20) CHECK (return_condition IN ('new', 'good', 'fair', 'damaged')),
    return_notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_dates CHECK (end_date >= start_date)
);

CREATE INDEX idx_loans_asset_id ON loans(asset_id);
CREATE INDEX idx_loans_user_id ON loans(user_id);
CREATE INDEX idx_loans_status ON loans(status);
CREATE INDEX idx_loans_dates ON loans(start_date, end_date);

-- 6. Loan History (Audit Trail)
CREATE TABLE loan_history (
    id SERIAL PRIMARY KEY,
    loan_id INT NOT NULL REFERENCES loans(id) ON DELETE CASCADE,
    action VARCHAR(50) NOT NULL,
    performed_by INT REFERENCES users(id) ON DELETE SET NULL,
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_loan_history_loan_id ON loan_history(loan_id);

-- 7. Maintenance Table
CREATE TABLE maintenance (
    id SERIAL PRIMARY KEY,
    asset_id INT NOT NULL REFERENCES equipment(id) ON DELETE RESTRICT,
    opened_by INT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    opened_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    issue_description TEXT NOT NULL,
    closed_by INT REFERENCES users(id) ON DELETE SET NULL,
    closed_at TIMESTAMP WITH TIME ZONE,
    resolution_notes TEXT,
    restored_condition VARCHAR(20) CHECK (restored_condition IN ('new', 'good', 'fair')),
    status VARCHAR(20) NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'resolved', 'cancelled'))
);

CREATE INDEX idx_maintenance_asset_id ON maintenance(asset_id);
CREATE INDEX idx_maintenance_status ON maintenance(status);

-- 8. Loan Events View (Page 4 Specification Alias)
CREATE OR REPLACE VIEW loan_events AS
SELECT 
    id,
    loan_id,
    performed_by AS actor_id,
    NULL::VARCHAR(50) AS from_status,
    action AS to_status,
    notes AS note,
    created_at
FROM loan_history;


