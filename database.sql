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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    status ENUM('pawned', 'redeemed', 'for_sale', 'sold') NOT NULL DEFAULT 'pawned',
    sale_price DECIMAL(10, 2),
    date_sold DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);
CREATE TABLE transactions (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT(11) UNSIGNED NOT NULL,
    transaction_type ENUM('pawn', 'renewal', 'redemption') NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id)
);