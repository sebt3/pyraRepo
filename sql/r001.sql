ALTER TABLE `users` CHANGE `username` `username` varchar(255) NOT NULL;
ALTER TABLE `user_tokens` CHANGE `keyname` `keyname` varchar(255) NOT NULL;
ALTER TABLE `langs` CHANGE `name` `name` varchar(255) NOT NULL;
ALTER TABLE `license_types` CHANGE `name` `name` varchar(255) NOT NULL;
ALTER TABLE `archs` CHANGE `name` `name` varchar(255) NOT NULL;
ALTER TABLE `categories` CHANGE `name` `name` varchar(255) NOT NULL;
ALTER TABLE `keywords` CHANGE `name` `name` varchar(255) NOT NULL;
ALTER TABLE `dbpackages`
	CHANGE `str_id` `str_id` varchar(255) NOT NULL,
	CHANGE `name` `name` varchar(255) NOT NULL;
ALTER TABLE `package_names` CHANGE `name` `name` varchar(255) NOT NULL;
ALTER TABLE `package_versions`
	CHANGE `version` `version` varchar(255) NOT NULL,
	CHANGE `md5sum` `md5sum` varchar(32),
	CHANGE `sha1sum` `sha1sum` varchar(40);
ALTER TABLE `apps` CHANGE `name` `name` varchar(255) NOT NULL;
ALTER TABLE `app_names` CHANGE `name` `name` varchar(255) NOT NULL;
