


CREATE USER 'dbprepo'@'localhost' IDENTIFIED BY 'dbprepo';

create database dbprepodb;
grant all privileges on dbprepodb.* to dbprepo@'%' identified by 'dbprepo';
grant all privileges on dbprepodb.* to dbprepo@localhost identified by 'dbprepo';


mysql -u dbprepo -D dbprepodb -p <sql/datamodel.sql
