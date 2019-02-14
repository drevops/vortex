-- Example of custom sanitization query.
--
-- Sanitization runs straight after database import and before any updates
-- and site bootstrap. It is useful to avoid working on real DB data.
UPDATE `users_field_data` SET `status` = '0' WHERE `uid` = '1' AND `langcode` = 'en';
