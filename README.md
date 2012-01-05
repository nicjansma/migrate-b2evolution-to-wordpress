Copyright (c) 2012 Nic Jansma
[http://nicj.net](http://nicj.net)

Converts a b2evolution blog to a WordPress blog.

Original blog post at [nicj.net](http://nicj.net/2009/04/01/b2evolution-09-to-wordpress-migration-script)

Instructions
------------
1. Install Wordpress - it does not have to use the same DB as your b2evolution blog.
2. Edit the connection info in `migrate-b2evolution-to-wordpress.php` (username, password, database) for both of your databases.
3. Run `migrate-b2evolution-to-wordpress.php`:

    `php -f migrate-b2evolution-to-wordpress.php`

4. Check over your posts, users, categories etc.
5. Enjoy your new Wordpress blog.

Version History
---------------
* v1.0 - 2009-04-01: Initial release

Credits
-------
* Created by Justin Mazzi (http://r00tshell.com)
* This script was originally found on (http://www.nocblog.com/software/2006/01/23/migrator-b2evolution-wordpress/)
* Modified by TuMahler (http://www.tumahler.com) 4/3/2006
* Modified by Nic Jansma (http://www.nicj.net) 3/1/2009