-- --------------------------------------------------------
-- inventory_management: full schema with stock accounting logic
-- Run on MySQL 5.7+ / 8.0+
-- --------------------------------------------------------

-- Drop & recreate database for a clean start (WARNING: destructive)
DROP DATABASE IF EXISTS `inventory_management`;
CREATE DATABASE `inventory_management` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;
USE `inventory_management`;

-- --------------------------------------------------------
-- stock_types: the three stock categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_types` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE, -- Aluminium, Alloy Steel, Kzinc
  `description` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- records: each stock item (belongs to a stock_type)
-- - no_of_meters stores the CURRENT meter balance for the record
-- - sales_status reflects overall state: 'Factory Use', 'In Production', 'Sold Out'
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `records` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `stock_type_id` INT NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `color` VARCHAR(100),
  `net_weight` DECIMAL(12,2),
  `gauge` VARCHAR(20),
  `sales_status` ENUM('Factory Use', 'In Production', 'Sold Out') NOT NULL DEFAULT 'Factory Use',
  `no_of_meters` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`stock_type_id`) REFERENCES `stock_types`(`id`) ON DELETE RESTRICT,
  INDEX (`stock_type_id`),
  INDEX (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- stock_accounting: ledger entries per record (inflow/outflow)
-- balance is the running balance after the transaction
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_accounting` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `record_id` INT NOT NULL,
  `entry_date` DATE NOT NULL,
  `transaction_type` ENUM('inflow','outflow') NOT NULL,
  `quantity_in` DECIMAL(14,2) DEFAULT 0.00,
  `quantity_out` DECIMAL(14,2) DEFAULT 0.00,
  `balance` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `remarks` VARCHAR(255),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`record_id`) REFERENCES `records`(`id`) ON DELETE CASCADE,
  INDEX (`record_id`),
  INDEX (`entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Insert the three stock types
-- --------------------------------------------------------
INSERT INTO `stock_types` (`name`, `description`) VALUES
  ('Aluminium', 'Aluminium stock type'),
  ('Alloy Steel', 'Alloy Steel stock type'),
  ('Kzinc', 'Kzinc stock type');

-- --------------------------------------------------------
-- Trigger: BEFORE INSERT on stock_accounting
-- - This computes the new balance for the transaction based on:
--   - the latest existing balance for this record (if any), OR
--   - the records.no_of_meters as fallback (opening balance).
-- - It sets NEW.balance automatically.
-- --------------------------------------------------------
DELIMITER $$

CREATE TRIGGER `stock_accounting_before_insert`
BEFORE INSERT ON `stock_accounting`
FOR EACH ROW
BEGIN
  DECLARE prev_balance DECIMAL(14,2) DEFAULT 0.00;
  DECLARE is_opening_balance BOOLEAN DEFAULT FALSE;
  
  -- Check if this is the first entry for this record (opening balance)
  SELECT COUNT(*) = 0 INTO is_opening_balance
  FROM stock_accounting
  WHERE record_id = NEW.record_id;
  
  -- For opening balance, start from 0
  IF is_opening_balance THEN
    SET prev_balance = 0.00;
  ELSE
    -- get latest balance for this record if exists
    SELECT sa.balance INTO prev_balance
    FROM stock_accounting sa
    WHERE sa.record_id = NEW.record_id
    ORDER BY sa.id DESC
    LIMIT 1;
    
    IF prev_balance IS NULL THEN
      SET prev_balance = 0.00;
    END IF;
  END IF;

  -- ensure quantity_in/quantity_out values are not null
  IF NEW.quantity_in IS NULL THEN
    SET NEW.quantity_in = 0.00;
  END IF;
  IF NEW.quantity_out IS NULL THEN
    SET NEW.quantity_out = 0.00;
  END IF;

  -- compute the new balance
  SET NEW.balance = prev_balance + NEW.quantity_in - NEW.quantity_out;

  -- Ensure balance cannot be negative below zero (optional business rule)
  IF NEW.balance < 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock: transaction would produce negative balance';
  END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------
-- Trigger: AFTER INSERT on stock_accounting
-- - Sync records.no_of_meters to the NEW.balance
-- - Update sales_status:
--    - If balance <= 0 => 'Sold Out'
--    - Else if there is more than one accounting entry for this record => 'In Production'
--    - Else (first entry and positive) => 'Factory Use'
-- --------------------------------------------------------
DELIMITER $$

CREATE TRIGGER `stock_accounting_after_insert`
AFTER INSERT ON `stock_accounting`
FOR EACH ROW
BEGIN
  DECLARE entry_count INT DEFAULT 0;
  DECLARE new_status ENUM('Factory Use','In Production','Sold Out');

  -- count accounting entries for this record
  SELECT COUNT(*) INTO entry_count FROM stock_accounting WHERE record_id = NEW.record_id;

  IF NEW.balance <= 0 THEN
    SET new_status = 'Sold Out';
  ELSE
    IF entry_count > 1 THEN
      SET new_status = 'In Production';
    ELSE
      SET new_status = 'Factory Use';
    END IF;
  END IF;

  -- update the records table with the new meter balance and status
  UPDATE records
     SET no_of_meters = NEW.balance,
         sales_status = new_status,
         updated_at = NOW()
   WHERE id = NEW.record_id;
END$$

DELIMITER ;

-- --------------------------------------------------------
-- Stored Procedure: create_record_with_opening
-- - Use this to insert a new record and create the opening stock accounting entry.
-- - It ensures the opening meters become the initial accounting balance for the record.
-- --------------------------------------------------------
DELIMITER $$

CREATE PROCEDURE `create_record_with_opening` (
  IN p_user_id INT,
  IN p_stock_type_id INT,
  IN p_code VARCHAR(50),
  IN p_color VARCHAR(100),
  IN p_net_weight DECIMAL(12,2),
  IN p_gauge VARCHAR(20),
  IN p_opening_meters DECIMAL(14,2),
  IN p_remarks VARCHAR(255)
)
BEGIN
  DECLARE new_record_id INT;

  -- Insert the record with initial no_of_meters = p_opening_meters and status = 'Factory Use'
  INSERT INTO records (user_id, stock_type_id, code, color, net_weight, gauge, sales_status, no_of_meters)
  VALUES (p_user_id, p_stock_type_id, p_code, p_color, p_net_weight, p_gauge, 'Factory Use', p_opening_meters);

  SET new_record_id = LAST_INSERT_ID();

  -- Create opening stock accounting entry (this will trigger the before/after triggers)
  INSERT INTO stock_accounting
    (record_id, entry_date, transaction_type, quantity_in, quantity_out, remarks)
  VALUES
    (new_record_id, CURDATE(), 'inflow', p_opening_meters, 0.00, COALESCE(p_remarks, 'Opening balance'));
END$$

DELIMITER ;

-- --------------------------------------------------------
-- Example seed data: create a sample user and then example records using the procedure
-- --------------------------------------------------------
INSERT INTO users (name, email, password) VALUES
  ('SHEMAIAH WAMBEBE YABA-SHIAKA', 'example@example.com', '$2y$12$...placeholderhash...');

-- Example: create three initial records (one per stock type) with opening meters
-- CALL create_record_with_opening(user_id, stock_type_id, code, color, net_weight, gauge, opening_meters, remarks);

CALL create_record_with_opening(1, (SELECT id FROM stock_types WHERE name='Aluminium' LIMIT 1),
  'AL-001', 'Silver', 2000.00, '0.50', 500.00, 'Initial aluminium stock');

CALL create_record_with_opening(1, (SELECT id FROM stock_types WHERE name='Alloy Steel' LIMIT 1),
  'AS-001', 'Dark', 3000.00, '0.70', 1000.00, 'Initial alloy steel stock');

CALL create_record_with_opening(1, (SELECT id FROM stock_types WHERE name='Kzinc' LIMIT 1),
  'KZ-001', 'Zinc Gray', 1500.00, '0.45', 750.00, 'Initial kzinc stock');

-- --------------------------------------------------------
-- Example: how to record transactions manually (inflow or outflow)
-- --------------------------------------------------------
-- Inflow example (adds 200 meters):
-- INSERT INTO stock_accounting (record_id, entry_date, transaction_type, quantity_in, quantity_out, remarks)
-- VALUES (1, '2025-11-01', 'inflow', 200.00, 0.00, 'Received from supplier');

-- Outflow example (removes 150 meters):
-- INSERT INTO stock_accounting (record_id, entry_date, transaction_type, quantity_in, quantity_out, remarks)
-- VALUES (1, '2025-11-02', 'outflow', 0.00, 150.00, 'Used in production');

-- The triggers will compute the new `balance` and update records.no_of_meters and sales_status automatically.

-- --------------------------------------------------------
-- Useful views (optional) - quick ledger per record
-- --------------------------------------------------------
CREATE OR REPLACE VIEW vw_record_current AS
SELECT r.id AS record_id, r.code, st.name AS stock_type, r.color, r.no_of_meters AS current_meters, r.sales_status, r.updated_at
FROM records r
JOIN stock_types st ON st.id = r.stock_type_id;

CREATE OR REPLACE VIEW vw_accounting_ledger AS
SELECT sa.id, sa.record_id, r.code, st.name AS stock_type, sa.entry_date, sa.transaction_type,
       sa.quantity_in, sa.quantity_out, sa.balance, sa.remarks, sa.created_at
FROM stock_accounting sa
JOIN records r ON r.id = sa.record_id
JOIN stock_types st ON st.id = r.stock_type_id
ORDER BY sa.record_id, sa.entry_date, sa.id;

-- --------------------------------------------------------
-- End of script
-- --------------------------------------------------------
