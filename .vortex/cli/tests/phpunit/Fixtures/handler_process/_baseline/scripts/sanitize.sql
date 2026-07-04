-- Example of custom sanitization query.
--
-- Sanitization runs straight after database import and before any updates
-- and site bootstrap. It is useful to avoid working on real DB data.
UPDATE `users_field_data` SET `status` = '0' WHERE `uid` = '1' AND `langcode` = 'en';

-- Below are the largest tables in the database that can be truncated.

-- Remove webform submissions.
-- CREATE TABLE IF NOT EXISTS `webform_submitted_data`;
-- TRUNCATE TABLE `webform_submitted_data`;

-- Remove queued items.
-- CREATE TABLE IF NOT EXISTS `queue`;
-- TRUNCATE TABLE `queue`;

-- CREATE TABLE IF NOT EXISTS `watchdog`;
-- TRUNCATE TABLE `watchdog`;
--
-- CREATE TABLE if NOT EXISTS `cache_entity`;
-- TRUNCATE TABLE `cache_entity`;
--
-- CREATE TABLE if NOT EXISTS `cache_discovery`;
-- TRUNCATE TABLE `cache_discovery`;
--
-- CREATE TABLE if NOT EXISTS `cache_default`;
-- TRUNCATE TABLE `cache_default`;
--
-- CREATE TABLE if NOT EXISTS `cache_data`;
-- TRUNCATE TABLE `cache_data`;
--
-- CREATE TABLE if NOT EXISTS `cache_menu`;
-- TRUNCATE TABLE `cache_menu`;
--
-- CREATE TABLE if NOT EXISTS `cache_config`;
-- TRUNCATE TABLE `cache_config`;

-- CREATE TABLE if NOT EXISTS `webform_submission_data`;
-- TRUNCATE TABLE `webform_submission_data`;
