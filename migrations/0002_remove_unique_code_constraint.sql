-- Migration to remove the unique constraint on the code column in the records table
ALTER TABLE `records` DROP INDEX `unique_user_code`;
