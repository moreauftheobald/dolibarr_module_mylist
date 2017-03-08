ALTER TABLE  `llx_mylist` DROP INDEX uk_mylist_code;
ALTER TABLE  `llx_mylist` ADD  `export`  INT DEFAULT 0 AFTER  `querydo`;
ALTER TABLE  `llx_mylist` ADD  `model_pdf`  INT DEFAULT 0 AFTER  `export`;
ALTER TABLE  `llx_mylist` ADD  `rowid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE  `llx_mylist` ADD  `description` TEXT NULL DEFAULT NULL AFTER  `rowid`;
