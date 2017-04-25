create table users (
	id		int(32) unsigned auto_increment,
	username	varchar(256) not null,
	firstname	varchar(128),
	lastname	varchar(128),
	passhash	varchar(512),
	isReal	 	tinyint(1) default true,
	constraint users_pk primary key(id),
	constraint unique index users_name_u(username)
);

create table user_tokens (
	id		int(32) unsigned auto_increment,
	user_id		int(32) unsigned not null,
	keyname		varchar(256) not null,
	passhash	varchar(512) not null,
	created		timestamp default current_timestamp,
	constraint user_tokens_pk primary key(id),
	constraint unique index user_tokens_name_u(keyname),
	constraint user_tokens_user_fk foreign key(user_id) references users(id) on delete cascade on update cascade
);
create index user_tokens_user_i on user_tokens(user_id);

create table langs (
	id 		int(32) unsigned auto_increment,
	name 		varchar(256) not null, 
	constraint langs_pk primary key (id),
	constraint unique index langs_u (name)
);
insert into langs(name) values('en'),('en_US'),('en_GB'), ('fr'),('fr_FR'), ('de'),('de_DE'), ('sv'),('sv_SE');

create table archs (
	id 		int(32) unsigned auto_increment,
	name 		varchar(256) not null, 
	constraint archs_pk primary key (id),
	constraint unique index archs_u (name)
);
insert into archs(name) values ('armhf'), ('x86'), ('armel');

create table categories (
	id 		int(32) unsigned auto_increment,
	name 		varchar(256) not null, 
	parent_id	int(32) unsigned,
	constraint categories_pk primary key (id),
	constraint unique index categories_u (name),
	constraint categories_parent_fk foreign key(parent_id) references categories(id) on delete cascade on update cascade
);
create index categories_parent_i on categories(parent_id);

create table keywords (
	id 		int(32) unsigned auto_increment,
	name 		varchar(256) not null, 
	constraint keywords_pk primary key (id),
	constraint unique index keywords_u (name)
);



create table dbpackages (
	id 		int(32) unsigned auto_increment,
	str_id 		varchar(256) not null,
	name 		varchar(256) not null,
	arch_id		int(32) unsigned not null,
	last_vers	int(32) unsigned,
	enabled 	tinyint(1) default true,
	icon		varchar(256),
	infos	 	mediumText,
	constraint dbpackages_pk primary key (id),
	constraint unique index dbpackages_u (str_id),
	constraint dbpackages_arch_fk foreign key(arch_id) references archs(id) on delete cascade on update cascade
);
create index dbpackages_arch_i on dbpackages(arch_id);
create index dbpackages_last_i on dbpackages(last_vers);

create table package_names (
	dbp_id 		int(32) unsigned not null,
	lang_id 	int(32) unsigned not null,
	name 		varchar(256) not null,
	constraint package_names_pk primary key (dbp_id, lang_id),
	constraint package_names_dbp_fk foreign key(dbp_id) references dbpackages(id) on delete cascade on update cascade,
	constraint package_names_lang_fk foreign key(lang_id) references langs(id) on delete cascade on update cascade
);
create index package_names_lang_i on package_names(lang_id);

create table package_versions (
	id 		int(32) unsigned auto_increment,
	dbp_id 		int(32) unsigned not null,
	by_user 	int(32) unsigned not null,
	version		varchar(256) not null,
	timestamp	double(20,4) unsigned not null,
	path		varchar(2048) not null,
	sys_deps	varchar(256),
	pkg_deps	varchar(256),
	enabled 	tinyint(1) default true,
	constraint package_versions_pk primary key (id),
	constraint unique index package_versions_u (dbp_id, version),
	constraint package_versions_dbp_fk foreign key(dbp_id) references dbpackages(id) on delete cascade on update cascade,
	constraint package_versions_user_fk foreign key(by_user) references users(id) on delete cascade on update cascade
);
create index package_versions_dbp_i on package_versions(dbp_id, timestamp);
alter table dbpackages add constraint dbpackages_last_fk foreign key(last_vers) references package_versions(id) on update cascade;

create table packages_maintainers (
	dbp_id 		int(32) unsigned not null,
	user_id 	int(32) unsigned not null,
	constraint packages_maintainers_pk primary key (dbp_id, user_id),
	constraint packages_maintainers_dbp_fk foreign key(dbp_id) references dbpackages(id) on delete cascade on update cascade,
	constraint packages_maintainers_user_fk foreign key(user_id) references users(id) on delete cascade on update cascade
);
create index packages_maintainers_user_i on packages_maintainers(user_id);

create table package_downloads (
	dbp_id 		int(32) unsigned not null,
	timestamp	double(20,4) unsigned not null,
	user_id 	int(32) unsigned not null,
	constraint package_downloads_pk primary key (dbp_id, user_id, timestamp),
	constraint package_downloads_dbp_fk foreign key(dbp_id) references dbpackages(id) on delete cascade on update cascade,
	constraint package_downloads_user_fk foreign key(user_id) references users(id) on delete cascade on update cascade
);
create index package_downloads_user_i on package_downloads(user_id, timestamp);



create table apps (
	id 		int(32) unsigned auto_increment,
	dbp_id 		int(32) unsigned not null,
	name 		varchar(256) not null,
	comments	varchar(2048),
	icon		varchar(256),
	infos	 	mediumText,
	enabled 	tinyint(1) default true,
	constraint apps_pk primary key (id),
	constraint unique index apps_u (dbp_id, name),
	constraint apps_dbp_fk foreign key(dbp_id) references dbpackages(id) on delete cascade on update cascade
);
create index apps_dbp_i on apps(dbp_id);

create table apps_categories (
	cat_id 		int(32) unsigned not null,
	app_id 		int(32) unsigned not null,
	constraint apps_categories_pk primary key (app_id, cat_id),
	constraint apps_categories_cat_fk foreign key(cat_id) references categories(id) on delete cascade on update cascade,
	constraint apps_categories_app_fk foreign key(app_id) references apps(id) on delete cascade on update cascade
);
create index apps_categories_cat_i on apps_categories(cat_id);

create table apps_keywords (
	key_id 		int(32) unsigned not null,
	app_id 		int(32) unsigned not null,
	constraint apps_keywords_pk primary key (app_id, key_id),
	constraint apps_keywords_key_fk foreign key(key_id) references keywords(id) on delete cascade on update cascade,
	constraint apps_keywords_app_fk foreign key(app_id) references apps(id) on delete cascade on update cascade
);
create index apps_keywords_key_i on apps_keywords(key_id);

create table app_names (
	app_id 		int(32) unsigned not null,
	lang_id 	int(32) unsigned not null,
	name 		varchar(256) not null,
	comments	varchar(256),
	constraint app_names_pk primary key (app_id, lang_id),
	constraint app_names_app_fk foreign key(app_id) references apps(id) on delete cascade on update cascade,
	constraint app_names_lang_fk foreign key(lang_id) references langs(id) on delete cascade on update cascade
);
create index app_names_lang_i on app_names(lang_id);

create table app_ratings (
	app_id 		int(32) unsigned not null,
	timestamp	double(20,4) unsigned not null,
	user_id 	int(32) unsigned not null,
	stars	 	tinyint(5) unsigned not null,
	constraint app_ratings_pk primary key (app_id, user_id),
	constraint app_ratings_app_fk foreign key(app_id) references apps(id) on delete cascade on update cascade,
	constraint app_ratings_user_fk foreign key(user_id) references users(id) on delete cascade on update cascade
);
create index app_ratings_user_i on app_ratings(user_id);

create table app_comments (
	app_id 		int(32) unsigned not null,
	timestamp	double(20,4) unsigned not null,
	user_id 	int(32) unsigned not null,
	text	 	mediumText not null,
	constraint app_comments_pk primary key (app_id, timestamp, user_id),
	constraint app_comments_app_fk foreign key(app_id) references apps(id) on delete cascade on update cascade,
	constraint app_comments_user_fk foreign key(user_id) references users(id) on delete cascade on update cascade
);
create index app_comments_user_i on app_comments(user_id, timestamp);

create table app_shoots (
	app_id 		int(32) unsigned not null,
	timestamp	double(20,4) unsigned not null,
	user_id 	int(32) unsigned not null,
	path		varchar(2048) not null,
	constraint app_shoots_pk primary key (app_id, timestamp, user_id),
	constraint app_shoots_app_fk foreign key(app_id) references apps(id) on delete cascade on update cascade,
	constraint app_shoots_user_fk foreign key(user_id) references users(id) on delete cascade on update cascade
);
create index app_shoots_user_i on app_shoots(user_id, app_id);

