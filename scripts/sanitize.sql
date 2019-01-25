-- Example of custom sanitisation query.
UPDATE `users_field_data` SET `status` = '0' WHERE `uid` = '1' AND `langcode` = 'en';
