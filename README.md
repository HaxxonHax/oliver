# Oliver

Oliver is code pulled from the dead project known as Oliver.  Oliver was a PHP
front-end to FTP, intended to allow people to access files, normally only 
available via FTP, using a web browser.  More information can be found in the
original README file.

## Why Oliver?

Our systems were running FTP and we need a quick secure solution before 
migrating all of our FTP customers to SFTP customers.  Oliver provided a web
front-end that we could host behind an Apache HTTPS proxy.  Newer systems,
such as Owncloud, have become viable solutions, but Oliver provided the easiest
learning curve and migration, since it used the native FTP client.  Adding to that,
we were able to turn off FTP to external clients, and only allow localhost
(127.0.0.1), closing off FTP vulnerabilities to the world.

## What's different here?

After Oliver was closed, I continued to add my own code to Oliver, including
support for "admin" and "settings".  I also separated out headers and footers
from the layout for branding of one's own pages.

Admin settings are only allowed for defined admins in the `config.php` file.
This allows one to set owners for a set of users, such that the admins can
maintain those users' passwords if needed (because our system doesn't tie into 
any directory service, self-maintainers were not a viable option).

Settings are allowed for non-admin users to change their own password.
