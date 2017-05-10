## What is this ?
This is the code for the [pyra repository](https://pyra-handheld.com/repo).
All contributions are welcome. Please submit your PR ;)

## Quick install guide

### Dependencies
Just get the dependencies using :
```composer install
```
### Database
As your mysql operator account : 

```CREATE USER 'dbprepo'@'localhost' IDENTIFIED BY 'dbprepo';
create database dbprepodb;
grant all privileges on dbprepodb.* to dbprepo@'%' identified by 'dbprepo';
grant all privileges on dbprepodb.* to dbprepo@localhost identified by 'dbprepo';
```

then :
```mysql -u dbprepo -D dbprepodb -p <sql/datamodel.sql
```
