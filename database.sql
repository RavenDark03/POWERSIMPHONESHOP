CREATE TABLE users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'manager') NOT NULL DEFAULT 'staff',
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE customers (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    -- Present Address
    present_house_num VARCHAR(50),
    present_street VARCHAR(100),
    present_subdivision VARCHAR(100),
    present_barangay VARCHAR(100) NOT NULL,
    present_city VARCHAR(100) NOT NULL,
    present_province VARCHAR(100) NOT NULL,
    present_zip VARCHAR(10),
    -- Permanent Address
    permanent_house_num VARCHAR(50),
    permanent_street VARCHAR(100),
    permanent_subdivision VARCHAR(100),
    permanent_barangay VARCHAR(100) NOT NULL,
    permanent_city VARCHAR(100) NOT NULL,
    permanent_province VARCHAR(100) NOT NULL,
    permanent_zip VARCHAR(10),
    contact_number VARCHAR(20) NOT NULL,
    id_type VARCHAR(50) NOT NULL,
    other_id_type VARCHAR(100),
    id_image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL
);
CREATE TABLE archived_customers (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT(11) UNSIGNED NOT NULL,
    customer_code VARCHAR(20) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    contact_number VARCHAR(20),
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_by INT(11) UNSIGNED NULL,
    INDEX (customer_id),
    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE items (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT(11) UNSIGNED NOT NULL,
    item_description TEXT NOT NULL,
    category VARCHAR(255) NOT NULL,
    item_type VARCHAR(50),
    brand VARCHAR(255),
    model VARCHAR(255),
    serial_number VARCHAR(255),
    accessories TEXT,
    weight_grams DECIMAL(10, 2),
    purity VARCHAR(50),
    gemstones TEXT,
    item_condition VARCHAR(50),
    loan_amount DECIMAL(10, 2) NOT NULL,
    interest_rate DECIMAL(5, 2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pawned', 'redeemed', 'for_sale', 'sold', 'archived') NOT NULL DEFAULT 'pawned',
    sale_price DECIMAL(10, 2),
    date_sold DATETIME,
    archived_at DATETIME NULL,
    archived_by INT(11) UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (archived_by) REFERENCES users(id)
);
CREATE TABLE transactions (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT(11) UNSIGNED NOT NULL,
    transaction_type ENUM('pawn', 'renewal', 'redemption') NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

-- Lookup tables for normalized item data
CREATE TABLE item_categories (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE item_types (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT(11) UNSIGNED DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (category_id, name),
    FOREIGN KEY (category_id) REFERENCES item_categories(id)
);

CREATE TABLE item_conditions (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optional notes table to store longer descriptions separately (one-to-one)
CREATE TABLE item_notes (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT(11) UNSIGNED NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

-- Add login and verification fields to customers table for hybrid registration flow
ALTER TABLE customers
    ADD COLUMN email VARCHAR(255) NOT NULL UNIQUE AFTER customer_code,
    ADD COLUMN username VARCHAR(255) UNIQUE AFTER email,
    ADD COLUMN password VARCHAR(255) AFTER username,
    ADD COLUMN registration_source ENUM('walk_in','online') NOT NULL DEFAULT 'walk_in' AFTER password,
    ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER registration_source,
    ADD COLUMN id_image_front_path VARCHAR(255) AFTER other_id_type,
    ADD COLUMN id_image_back_path VARCHAR(255) AFTER id_image_front_path;

-- Add birthdate column for customer accounts
ALTER TABLE customers
    ADD COLUMN birthdate DATE AFTER last_name;

-- Add verification token fields for email confirmation
ALTER TABLE customers
    ADD COLUMN verification_token VARCHAR(255) NULL AFTER is_verified,
    ADD COLUMN verification_expires DATETIME NULL AFTER verification_token;

-- Password reset token fields
ALTER TABLE customers
    ADD COLUMN password_reset_token VARCHAR(255) NULL AFTER verification_expires,
    ADD COLUMN password_reset_expires DATETIME NULL AFTER password_reset_token;

-- Account approval status for online registrations
-- 'approved' = can login (default for walk-in), 'pending' = awaiting staff approval, 'rejected' = denied
ALTER TABLE customers
    ADD COLUMN account_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER is_verified;