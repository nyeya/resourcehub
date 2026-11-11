-- ResourceHub PostgreSQL Pure-PHP Dump
-- Generated: 2026-11-12 10:02:23 UTC
-- Database: resourcehub

SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;

DROP TABLE IF EXISTS loan_history CASCADE;
DROP TABLE IF EXISTS maintenance CASCADE;
DROP TABLE IF EXISTS loans CASCADE;
DROP TABLE IF EXISTS equipment CASCADE;
DROP TABLE IF EXISTS locations CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Schema definitions
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




-- Data for table: users
INSERT INTO users ("id", "name", "email", "password_hash", "role", "is_active", "created_at", "updated_at") VALUES (1, 'Alice Admin', 'admin@resourcehub.local', '$2y$12$c4VuTsLb/SoxxRmvuLw/peBK1HmKayE802c1T1ivNuW2nMum/Faxe', 'administrator', TRUE, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO users ("id", "name", "email", "password_hash", "role", "is_active", "created_at", "updated_at") VALUES (2, 'Charlie Coordinator', 'coordinator@resourcehub.local', '$2y$12$c4VuTsLb/SoxxRmvuLw/peBK1HmKayE802c1T1ivNuW2nMum/Faxe', 'coordinator', TRUE, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO users ("id", "name", "email", "password_hash", "role", "is_active", "created_at", "updated_at") VALUES (3, 'Sarah Staff', 'staff1@resourcehub.local', '$2y$12$c4VuTsLb/SoxxRmvuLw/peBK1HmKayE802c1T1ivNuW2nMum/Faxe', 'staff', TRUE, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO users ("id", "name", "email", "password_hash", "role", "is_active", "created_at", "updated_at") VALUES (4, 'Sam Fieldwork', 'staff2@resourcehub.local', '$2y$12$c4VuTsLb/SoxxRmvuLw/peBK1HmKayE802c1T1ivNuW2nMum/Faxe', 'staff', TRUE, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO users ("id", "name", "email", "password_hash", "role", "is_active", "created_at", "updated_at") VALUES (5, 'Sophia Research', 'staff3@resourcehub.local', '$2y$12$c4VuTsLb/SoxxRmvuLw/peBK1HmKayE802c1T1ivNuW2nMum/Faxe', 'staff', TRUE, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO users ("id", "name", "email", "password_hash", "role", "is_active", "created_at", "updated_at") VALUES (6, 'Ian Inactive', 'inactive@resourcehub.local', '$2y$12$c4VuTsLb/SoxxRmvuLw/peBK1HmKayE802c1T1ivNuW2nMum/Faxe', 'staff', FALSE, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE((SELECT MAX(id) FROM users), 1), true);

-- Data for table: categories
INSERT INTO categories ("id", "name", "description", "created_at") VALUES (1, 'Laptops & Computers', 'Laptops, mobile workstations and tablets for portable computing', '2026-11-12 09:37:11.407489+00');
INSERT INTO categories ("id", "name", "description", "created_at") VALUES (2, 'Audio/Visual & Media', 'Cameras, audio recorders, microphones, tripods and projection equipment', '2026-11-12 09:37:11.407489+00');
INSERT INTO categories ("id", "name", "description", "created_at") VALUES (3, 'Laboratory Instruments', 'Specialised meters, sensors, scales, oscilloscopes and testing tools', '2026-11-12 09:37:11.407489+00');
INSERT INTO categories ("id", "name", "description", "created_at") VALUES (4, 'Fieldwork & Surveying', 'GPS units, outdoor data loggers, drills and environmental survey gear', '2026-11-12 09:37:11.407489+00');
SELECT setval(pg_get_serial_sequence('categories', 'id'), COALESCE((SELECT MAX(id) FROM categories), 1), true);

-- Data for table: locations
INSERT INTO locations ("id", "name", "building", "room", "created_at") VALUES (1, 'Central Equipment Store', 'Admin Block A', 'Room 102', '2026-11-12 09:37:11.407489+00');
INSERT INTO locations ("id", "name", "building", "room", "created_at") VALUES (2, 'Science & Tech Lab Hub', 'Engineering Wing C', 'Lab 205', '2026-11-12 09:37:11.407489+00');
INSERT INTO locations ("id", "name", "building", "room", "created_at") VALUES (3, 'Field Station Depot', 'South Station', 'Depot Bay 4', '2026-11-12 09:37:11.407489+00');
SELECT setval(pg_get_serial_sequence('locations', 'id'), COALESCE((SELECT MAX(id) FROM locations), 1), true);

-- Data for table: equipment
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (1, 'EQ-1001', 'Dell Latitude 5420 Laptop', 1, 1, 'Latitude 5420', 'DL5420-9841', 'good', 'available', '16GB RAM, 512GB SSD, Windows 11 Pro', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (2, 'EQ-1002', 'Lenovo ThinkPad T14 Gen 3', 1, 1, 'ThinkPad T14', 'TP14-3329', 'new', 'available', 'AMD Ryzen 7, 32GB RAM', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (3, 'EQ-1003', 'Apple MacBook Pro 14" M2', 1, 2, 'MacBook Pro 14', 'MBP14-8842', 'good', 'available', 'Includes 67W USB-C power adapter', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (4, 'EQ-1004', 'Sony Alpha a7 IV Mirrorless Camera', 2, 1, 'ILCE-7M4', 'SNY7-4401', 'damaged', 'maintenance', 'Front lens mount damaged during fieldwork return; currently in repair', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (5, 'EQ-1005', 'Canon XA40 Professional Camcorder', 2, 1, 'XA40 4K', 'CNXA-5521', 'good', 'available', 'Includes XLR audio handle and battery charger', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (6, 'EQ-1006', 'Panasonic Lumix GH5 Camera Kit', 2, 1, 'DC-GH5', 'PANG-1092', 'fair', 'available', 'Includes 12-60mm lens and 2 batteries', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (7, 'EQ-1007', 'DJI Mic 2 Dual Wireless Audio Kit', 2, 2, 'DJI-MIC2', 'DJMC-9081', 'good', 'available', 'Charging case, 2 transmitters, 1 receiver', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (8, 'EQ-1008', 'Epson EB-2250U High-Lumen Projector', 2, 1, 'EB-2250U', 'EPSN-7734', 'good', 'available', '5,000 lumens, HDMI and VGA inputs', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (9, 'EQ-1009', 'Rode VideoMic Pro+ Shotgun Mic', 2, 2, 'VMP+', 'RODE-6211', 'good', 'available', 'Camera shoe-mount condenser microphone', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (10, 'EQ-1010', 'Fluke 87V Industrial Multimeter', 3, 2, '87V Max', 'FLK8-1123', 'good', 'available', 'True-RMS industrial multimeter with probe leads', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (11, 'EQ-1011', 'Rigol DS1054Z Digital Oscilloscope', 3, 2, 'DS1054Z', 'RIGL-4902', 'good', 'available', '50 MHz 4-channel digital oscilloscope with probes', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (12, 'EQ-1012', 'Ohaus Pioneer Precision Balance Scale', 3, 2, 'PX224', 'OHAU-3004', 'fair', 'available', 'Calibrated to 0.1 mg precision; handle with care', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (13, 'EQ-1013', 'Hach HQ40D Multi-Parameter Water Meter', 3, 2, 'HQ40D', 'HACH-8192', 'good', 'available', 'pH, conductivity and dissolved oxygen probes', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (14, 'EQ-1014', 'FLIR E6-XT Infrared Thermal Camera', 3, 2, 'E6-XT', 'FLIR-9912', 'new', 'available', '240x180 resolution thermal imaging camera', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (15, 'EQ-1015', 'Garmin GPSMAP 66sr Handheld GPS', 4, 3, 'GPSMAP 66sr', 'GRMN-5012', 'good', 'available', 'Multi-band satellite frequency support with TOPO maps', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (16, 'EQ-1016', 'Trimble Catalyst DA2 GNSS Receiver', 4, 3, 'DA2', 'TRMB-2219', 'good', 'available', 'Centimeter-accuracy mobile GNSS antenna and pole', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (17, 'EQ-1017', 'Decagon 5TM Soil Moisture Sensor Kit', 4, 3, '5TM-Pro', 'DCGN-7120', 'good', 'available', 'Includes 5 probes and handheld data logger', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (18, 'EQ-1018', 'Kestrel 5500 Weather Meter', 4, 3, '5500 Link', 'KEST-3301', 'good', 'available', 'Wind speed, temperature, humidity and barometric tracking', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (19, 'EQ-1019', 'DJI Mavic 3 Enterprise Survey Drone', 4, 3, 'M3E', 'DJME-0029', 'good', 'available', 'Includes RTK module, 3 batteries and hard case', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO equipment ("id", "asset_tag", "name", "category_id", "location_id", "model", "serial_number", "condition", "status", "notes", "created_at", "updated_at") VALUES (20, 'EQ-1020', 'Bosch GRL 900-26 Rotary Laser Level', 4, 3, 'GRL 900', 'BSCH-6610', 'fair', 'retired', 'Retired from active loaning due to out-of-spec leveling calibration', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
SELECT setval(pg_get_serial_sequence('equipment', 'id'), COALESCE((SELECT MAX(id) FROM equipment), 1), true);

-- Data for table: loans
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (1, 1, 3, '2026-11-05', '2026-11-10', 'checked_out', 'Field survey data capture in North District', NULL, 2, '2026-11-04 09:00:00+00', 2, '2026-11-05 09:00:00+00', NULL, NULL, NULL, NULL, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (2, 2, 4, '2026-11-11', '2026-11-15', 'checked_out', 'Onsite environmental data compilation', NULL, 2, '2026-11-10 09:00:00+00', 2, '2026-11-11 09:00:00+00', NULL, NULL, NULL, NULL, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (3, 3, 5, '2026-11-14', '2026-11-17', 'approved', 'Machine learning workshop presentation', NULL, 2, '2026-11-11 09:00:00+00', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (4, 7, 3, '2026-11-16', '2026-11-19', 'pending', 'Interview recording for departmental documentary', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (5, 7, 4, '2026-11-17', '2026-11-20', 'pending', 'Audio recording for conference proceedings', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (6, 10, 5, '2026-11-13', '2026-11-16', 'pending', 'Electronics testing for prototype testing board', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (7, 15, 3, '2026-11-18', '2026-11-21', 'pending', 'Forestry plot boundary mapping project', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (8, 8, 4, '2026-10-29', '2026-10-31', 'returned', 'Guest lecture projection in Auditorium', NULL, 2, '2026-10-28 09:00:00+00', 2, '2026-10-29 09:00:00+00', 2, '2026-10-31 09:00:00+00', 'good', 'Returned on time in full working order with power cable and remote.', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (9, 4, 5, '2026-11-06', '2026-11-09', 'returned', 'Field photo documentation', NULL, 2, '2026-11-05 09:00:00+00', 2, '2026-11-06 09:00:00+00', 2, '2026-11-09 09:00:00+00', 'damaged', 'Camera returned with loose lens bayonet. Transferred to maintenance.', '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (10, 19, 3, '2026-11-08', '2026-11-10', 'rejected', 'Aerial aerial mapping without certified pilot license', 'Pilot certification not submitted with application.', 2, '2026-11-07 09:00:00+00', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (11, 11, 4, '2026-11-15', '2026-11-18', 'cancelled', 'Laboratory session rescheduled by department', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
INSERT INTO loans ("id", "asset_id", "user_id", "start_date", "end_date", "status", "purpose", "rejection_reason", "approved_by", "approved_at", "checked_out_by", "checked_out_at", "returned_by", "returned_at", "return_condition", "return_notes", "created_at", "updated_at") VALUES (12, 14, 5, '2026-11-15', '2026-11-19', 'approved', 'Thermal insulation evaluation of faculty laboratories', NULL, 2, '2026-11-12 09:00:00+00', NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-12 09:37:11.407489+00', '2026-11-12 09:37:11.407489+00');
SELECT setval(pg_get_serial_sequence('loans', 'id'), COALESCE((SELECT MAX(id) FROM loans), 1), true);

-- Data for table: maintenance
INSERT INTO maintenance ("id", "asset_id", "opened_by", "opened_at", "issue_description", "closed_by", "closed_at", "resolution_notes", "restored_condition", "status") VALUES (1, 4, 2, '2026-11-09 09:37:11+00', 'Camera lens mount flange bent upon return. Awaiting replacement parts and alignment test.', NULL, NULL, NULL, NULL, 'open');
SELECT setval(pg_get_serial_sequence('maintenance', 'id'), COALESCE((SELECT MAX(id) FROM maintenance), 1), true);

-- Data for table: loan_history
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (1, 1, 'requested', 3, 'Loan request submitted.', '2026-11-04 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (2, 1, 'approved', 2, 'Loan request approved by coordinator.', '2026-11-04 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (3, 1, 'checked_out', 2, 'Equipment handed over and checked out to borrower.', '2026-11-05 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (4, 2, 'requested', 4, 'Loan request submitted.', '2026-11-10 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (5, 2, 'approved', 2, 'Loan request approved by coordinator.', '2026-11-10 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (6, 2, 'checked_out', 2, 'Equipment handed over and checked out to borrower.', '2026-11-11 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (7, 3, 'requested', 5, 'Loan request submitted.', '2026-11-11 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (8, 3, 'approved', 2, 'Loan request approved by coordinator.', '2026-11-11 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (9, 4, 'requested', 3, 'Loan request submitted.', '2026-11-11 09:37:11+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (10, 5, 'requested', 4, 'Loan request submitted.', '2026-11-11 09:37:11+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (11, 6, 'requested', 5, 'Loan request submitted.', '2026-11-11 09:37:11+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (12, 7, 'requested', 3, 'Loan request submitted.', '2026-11-11 09:37:11+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (13, 8, 'requested', 4, 'Loan request submitted.', '2026-10-28 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (14, 8, 'approved', 2, 'Loan request approved by coordinator.', '2026-10-28 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (15, 8, 'checked_out', 2, 'Equipment handed over and checked out to borrower.', '2026-10-29 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (16, 8, 'returned', 2, 'Equipment physically returned: condition recorded as good.', '2026-10-31 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (17, 9, 'requested', 5, 'Loan request submitted.', '2026-11-05 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (18, 9, 'approved', 2, 'Loan request approved by coordinator.', '2026-11-05 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (19, 9, 'checked_out', 2, 'Equipment handed over and checked out to borrower.', '2026-11-06 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (20, 9, 'returned', 2, 'Equipment physically returned: condition recorded as damaged.', '2026-11-09 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (21, 10, 'requested', 3, 'Loan request submitted.', '2026-11-07 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (22, 10, 'rejected', 2, 'Loan request rejected: Pilot certification not submitted with application.', '2026-11-07 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (23, 11, 'requested', 4, 'Loan request submitted.', '2026-11-11 09:37:11+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (24, 11, 'cancelled', 4, 'Loan request cancelled by requester.', '2026-11-12 09:37:11+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (25, 12, 'requested', 5, 'Loan request submitted.', '2026-11-12 09:00:00+00');
INSERT INTO loan_history ("id", "loan_id", "action", "performed_by", "notes", "created_at") VALUES (26, 12, 'approved', 2, 'Loan request approved by coordinator.', '2026-11-12 09:00:00+00');
SELECT setval(pg_get_serial_sequence('loan_history', 'id'), COALESCE((SELECT MAX(id) FROM loan_history), 1), true);

