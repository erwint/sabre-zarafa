# Sabre-Zarafa

The aim of this project is to provide a full CardDav backend for
[SabreDAV](http://code.google.com/p/sabredav) to connect with
[Zarafa](http://www.zarafa.com) groupware.

Tarballs and zipfiles of the source can be downloaded
[here](https://github.com/bokxing-it/sabre-zarafa/tags). For changes, see the
[ChangeLog](https://github.com/bokxing-it/sabre-zarafa/blob/master/ChangeLog).

Sabre-Zarafa is a backend for the SabreDAV server. SabreDAV is a generic DAV
server that processes CardDAV, CalDAV and WebDAV requests. It handles all the
intricate parts of the DAV protocols and client communication, but doesn't know
anything about databases or stores. It defers the responsibility for providing
abstract objects like "cards" and "address books" to backend software like
Sabre-Zarafa, which is free to implement retrieval and storage however it
likes.

Sabre-Zarafa knows nothing about DAV, but does know how to get data from the
Zarafa server and convert it to VCard format. Together, the SabreDAV frontend
and the Sabre-Zarafa backend combine to form a Zarafa-based CardDAV server.

## License

Sabre-Zarafa is licensed under the terms of the [GNU Affero General Public
License, version 3](http://www.gnu.org/licenses/agpl-3.0.html).

## Install

### Introduction

This installs as any [SabreDAV](http://code.google.com/p/sabredav) server.
Unpack the source into a directory. This manual will assume
`/var/www/htdocs/sabre-zarafa` as the root.

As of version 0.18, Sabre-Zarafa is written against the SabreDAV 1.8 API, and
[SabreDAV](http://code.google.com/p/sabredav) no longer comes included. Since
the directory layout changed in SabreDAV 1.8, it no longer makes sense to
bundle parts of it, and bundling the whole package seems excessive.

You have to download a SabreDAV release from the 1.8 series yourself and unzip
it in the `/lib` directory:

    # cd /var/www/htdocs/sabre-zarafa/lib
    # unzip /path/to/SabreDAV-1.8.x.zip

Sabre-Zarafa logs using [Apache log4php](http://logging.apache.org/log4php).
You have to [download](http://logging.apache.org/log4php/download.html) a copy
and move the files in `/src/main/php/` to the `/lib/log4php` directory:

    # tar xvzf apache-log4php-2.3.0-src.tar.gz
    # mv apache-log4php-2.3.0/src/main/php/ /var/www/htdocs/sabre-zarafa/lib/log4php

See below on how to configure `log4php.xml`.

The webserver needs to write to the `data` directory, since it is used by
SabreDAV to store DAV locks. The log file, called `debug.txt`, should also be
writable. If your server runs as the user `apache`:

    # chown apache:apache /var/www/htdocs/sabre-zarafa/data
    # chmod 0750 /var/www/htdocs/sabre-zarafa/data
    # chown apache:apache /var/www/htdocs/sabre-zarafa/debug.txt
    # chmod 0640 /var/www/htdocs/sabre-zarafa/debug.txt

### Sabre-Zarafa configuration

Sabre-Zarafa needs additional configuration in `config.inc.php`. You must set
`CARDDAV_ROOT_URI` to the proper value. This is the path from the root of the
webserver to Sabre-Zarafa. If Sabre-Zarafa is installed in the webserver root,
then use `/`. If Sabre-Zarafa is installed in `/var/www/htdocs/sabre-zarafa`
and `/var/www/htdocs` is the server root, then put `/sabre-zarafa`. If you're
not using `mod_redirect` to redirect requests to `server.php`, use
`/sabre-zarafa/server.php`.

You can also adjust other settings, some are highly experimental (non-UTF8
vcards for instance) and should be used only for testing.

If you prefer to only use read operations and not make any edits to the
database, set `READ_ONLY` to `true`.

### Running from the root of the webserver

According to the SabreDAV documentation, you get the least issues if you run
the service straight from the root of the webserver. You can run it on port 80,
but for CardDAV, it makes some sense to use port 8008, since that's what OSX
Addressbook uses by default. To configure Apache to listen to port 8008 and use
a virtual host to serve Sabre-Zarafa, put something like the following
configuration in `httpd.conf`:

    Listen 8008

    <VirtualHost *:8008>

        # ...general server options, enable PHP parsing, etc...

        DocumentRoot /var/www/htdocs/sabre-zarafa
        <Directory /var/www/htdocs/sabre-zarafa>
            DirectoryIndex server.php
            RewriteEngine On
            RewriteBase /

            # If the request does not reference an actual plain file or
            # directory (such as server.php), interpret it as a "virtual path"
            # and pass it to server.php:
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^.*$ /server.php

            # If you're getting 412 Precondition Failed errors, try turning off ETag support:
            # RequestHeader unset If-None-Match
            # Header unset ETag
            # FileETag None
        </Directory>

        # Files and directories writable by the server should never be public:
        <Directory /var/www/htdocs/sabre-zarafa/data>
            Deny from all
        </Directory>
        <Files /var/www/htdocs/sabre-zarafa/debug.txt>
            Deny from all
        </Files>
    </VirtualHost>

Don't forget to edit `config.inc.php` and change `CARDDAV_ROOT_URI` to `/`.

### Running from a subdirectory

You can also run Sabre-Zarafa in a subdirectory of your webserver. In that
case, use a variant of this configuration:

    <Directory /var/www/htdocs/sabre-zarafa>
        DirectoryIndex server.php
        RewriteEngine On
        RewriteBase /sabre-zarafa

        # If the request does not reference an actual plain file or directory
        # (such as server.php), interpret it as a "virtual path" and pass it to
        # server.php:
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^.*$ /sabre-zarafa/server.php

        # If you're getting 412 Precondition Failed errors, try turning off ETag support:
        # RequestHeader unset If-None-Match
        # Header unset ETag
        # FileETag None
    </Directory>

    # Files and directories writable by the server should never be public:
    <Directory /var/www/htdocs/sabre-zarafa/data>
        Deny from all
    </Directory>
    <Files /var/www/htdocs/sabre-zarafa/debug.txt>
        Deny from all
    </Files>

Edit `config.inc.php` and change `CARDDAV_ROOT_URI` to `/sabre-zarafa`.

### Authentication

Please note that the authentification backend uses Basic auth. Some clients
will only work with Basic auth if the host uses SSL. It is not possible to use
Digest authentication, because Sabre-Zarafa needs the plaintext password to log
in to the Zarafa server.

Some detailed information about SabreDAV setup are available in [SabreDAV
documentation](http://code.google.com/p/sabredav/wiki/Introduction). Do not
hesitate to read it!

## PHP 5.1 and 5.2 version

As of version 0.18, Sabre-Zarafa requires SabreDAV 1.8, which in turn requires
PHP 5.3.

As of 0.15 Sabre-Zarafa uses SabreDAV 1.6.1 which requires PHP 5.3. If you use
older versions of PHP you will need to revert to official SabreDAV 1.5 (PHP
5.2). If you use PHP 5.1 you should look for the specific PHP 5.1 branch of
SabreDAV, download it and install it in the `lib` directory. This configuration
has not been fully tested.

## Debugging

### Log4Php

Starting with release 0.10 [log4php](http://logging.apache.org/log4php/) is used
for debugging. To configure logging you need to edit `log4php.xml`. Default
setup is to log WARN and FATAL messages to `debug.txt` with a maximum size of
5MB for logfile and 3 backup indexes (`debug.txt.1`).

To disable debugging simply set root appender to `noDebug`.

Make sure the path to `debug.txt` in `log4php.xml` is absolute:

    <param name="file" value="/var/www/htdocs/sabre-zarafa/debug.txt" />

Log4PHP allow you to log selected messages the way you want. For instance one
could log connection failed messages to syslog or to a database. See [log4php
website](http://logging.apache.org/log4php/) for details!

### Releases 0.9 and before

To enable debugging you need to create a debug.txt file in your sabre-zarafa
installation directory. The web server needs write permissions for this file.
This is __very verbose__ especially if you use contact pictures! so enable only
if needed/asked for.

No password is stored in this debug file.
