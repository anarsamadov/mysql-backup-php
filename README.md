### Backup MySQL Database Using PHP

Originally adapted from [David Walsh Blog](https://davidwalsh.name/backup-mysql-database-php)'s wonderful backup script. The script was written a long time ago (about 8 years), so MySQL connections were handled not in a good way. The MySQL extension is deprecated and is going to be removed in the future, so I modified MySQL driver to PDO. 

##### Usage
Fill the database parameters given on the script. 
```php
define("DB_USER",     'example_user');
define("DB_PASSWORD", 'a_very_strong_password');
define("DB_NAME",     'example_database');
define("DB_HOST",     'example.com');
define("OUTPUT_DIR",  '/an/example/directory');
define("TABLES",      '*'); // use * for all tables, or table names seperated with comma 
```

You can execute it from terminal by running ``php backup.php``, or if you want, you can add a cron job to do it automatically. *Example:* ``0 3 * * * php /path/to/backup.php``

##### Anything else?
Feel free to report bug, contribute or improve the code.  