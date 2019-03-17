-- Example of custom sanitization query.
--
-- Sanitization runs straight after database import and before any updates
-- and site bootstrap. It is useful to avoid working on real DB data.
UPDATE `users_field_data` SET `status` = '0' WHERE `uid` = '1' AND `langcode` = 'en';

-- Below are the largest tables in the database that can be truncated.

create table if not exists `watchdog`;
TRUNCATE TABLE `watchdog`;

create table if not exists `cache_entity`;
TRUNCATE TABLE `cache_entity`;

create table if not exists `cache_discovery`;
TRUNCATE TABLE `cache_discovery`;

create table if not exists `cache_default`;
TRUNCATE TABLE `cache_default`;

create table if not exists `cache_data`;
TRUNCATE TABLE `cache_data`;

create table if not exists `cache_menu`;
TRUNCATE TABLE `cache_menu`;

create table if not exists `cache_config`;
TRUNCATE TABLE `cache_config`;

-- create table if not exists `webform_submission_data`;
-- TRUNCATE TABLE `webform_submission_data`;
