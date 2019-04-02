-- Example of custom sanitization query.
--
-- Sanitization runs straight after database import and before any updates
-- and site bootstrap. It is useful to avoid working on real DB data.
UPDATE `users` SET `status` = '0' WHERE `uid` = '1';

-- Below are the largest tables in the database that can be truncated.

-- CREATE TABLE if NOT EXISTS `watchdog`;
-- TRUNCATE TABLE `watchdog`;
