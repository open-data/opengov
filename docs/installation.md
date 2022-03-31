# Installation Guide

This guide will allow you to setup your development environment for the OpenGov project. It is assumed that you already have CentOS 7
installed in either a virtual machine or a bootable partition. The Centos 7 ISO can be found here: https://wiki.centos.org/Download


## Pre-requisites

### 1. PHP
Install and configure PHP7.2 

```
	$ sudo yum install epel-release
	$ sudo yum install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
	$ sudo yum install yum-utils
	$ sudo yum-config-manager --enable remi-php72
	$ sudo yum update  ### this step may take a while ###
	$ sudo yum install php
	$ sudo yum install php-gd php-json php-mbstring php-xml php-xmlrpc php-opcache php-pgsql php-pdo
```

### 2. PHP-FPM
PHP-FPM allows our webserver to execute and serve php files. To install php-fpm, run the following in a terminal
```
	$ sudo yum install php-fpm
```
To start the php-fpm service, run 
```
	$ sudo systemctl start php-fpm
```

### 3. Composer
Composer is a tool used to manage dependencies in your Drupal project. To install, run the following commands from your terminal:

```
	$ php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	$ php -r "if (hash_file('sha384', 'composer-setup.php') === 'a5c698ffe4b8e849a443b120cd5ba38043260d5c4023dbf93e1558871f1f07f58274fc6f4c93bcfd858c6bd0775cd8d1') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
	$ php composer-setup.php
	$ php -r "unlink('composer-setup.php');"
```
This will download the setup file and run it, producing a composer.phar file.

Composer can now be called locally using 
```	
	$ php composer.phar [commands]
```
To install composer globally, execute
```
	$ sudo mv composer.phar /usr/local/bin/composer
```
This will allow you to simply use `composer [commands]` instead of `php composer.phar [commands]`


### 4. PostgreSQL
PostgreSql is the RDMS used in our stack. To install and start the Postgresql server:
```
	$ sudo yum install postgresql-server postgresql-contrib
	$ sudo postgresql-setup initdb
	$ sudo systemctl start postgresql
``` 
This will create a *Linux* user named postgres as well as a PostgreSQL Server user named postgres

First, change the password of the *Linux* user name postgres using:
```
	$ sudo passwd postgres
```
You will be prompted to enter a new password twice. You can now run:
```
	$ su - postgres
```
This will open a new prompt under the Linux postgres user. The default directory of this prompt is the directory where
the PostgreSQL server is installed. The default configuration of the PostgreSQL server doesn't allow outside connections.
To change this, navigate to the `data` directory while, and modify the lines in `pg_hba.conf` from:

```
# "local" is for Unix domain socket connections only
local   all             all                                     peer
# IPv4 local connections:
host    all             all             127.0.0.1/32            ident
# IPv6 local connections:
host    all             all             ::1/128                 ident
```
to
```
# "local" is for Unix domain socket connections only
local   all             all                                  	peer
# IPv4 local connections:
host    all             all             127.0.0.1/32            md5
# IPv6 local connections:
host    all             all             ::1/128                 md5
```
Next, uncomment and modify the lines in postgresql.conf from:
```
#listen_address = 'localhost'
#port = 5432
```
to
```
listen_address = '*'
port = 5432
```
Next create a new user,
```
	$ createuser your_username -pwprompt
```
Where 'your_username' should be replaced with your own username. After running this command you will be prompted to create
a password. Finally, log in to the postgresql server with
```
	$ psql postgres
```
and give your new user permission to create database
```
	postgres=> ALTER USER your_username CREATEDB;
```
Where 'your_username' is the user you just created.

To return to your regular shell, enter
```
	postgres=> \q
```
To leave the PostgreSQL server, followed by
```
	$ exit
```
To log out of the postgres Linux user. For good measure, restart the postgresql server,
```
	$ sudo systemctl restart postgresql.service
```

### 5. Memcached
Memcached is a php extension required by our Drupal project. To install:
```
	$ sudo yum install memcached
```
Start the service with 
```
	$ sudo systemctl start memcached
```

### 6. NGINX
NGINX is the webserver we will be using. To install and run nginx
```
	$ sudo yum install nginx
	$ sudo systemctl start nginx
```

### 7. Solr
```
	$ wget http://apache.org/dist/lucene/solr/8.2.0/solr-8.2.0.tgz
	$ tar xzf solr-8.2.0.tgz solr-8.2.0/bin/install_solr_service.sh --strip-components=2
	$ sudo bash ./install_solr_service.sh solr-8.2.0.tgz
```
This will automatically start the solr service on port 8983. However, for some reason this process
can't be controlled using the systemctl commands. To fix that, terminate the process and restart
the solr service using
```
	$ sudo kill -9 [PID] ###PID is the process ID of the solr service
	$ sudo systemctl restart solr.service
```

## Installation
To create a Drupal project using composer, navigate to a directory where you would like to create your Drupal project and run
```
	$ composer create-project opengov/opengov-project:dev-master MYPROJECT --no-interaction
```
Where MYPROJECT is the name of your project.


### Configuring NGINX

The NGINX webserver configuration file can be found in `/etc/nginx/nginx.conf`. For help writing the config
file, refer to https://www.linode.com/docs/web-servers/nginx/serve-php-php-fpm-and-nginx/#install-and-configure-php-fpm
for setting up the nginx web server with php-fpm enabled. In order to run the interactive installer for the next step,
you must at least have the following lines added to your server block.

```
server {
    listen       80;
    server_name  10.0.2.15 127.0.0.1;

#    error_page 500 502 503 504 /custom_50x.html;
#    location = /custom_50x.html {
#        root /opt/tbs/wcms/;
#        internal;
#    }

    location /healthcheck {
       try_files $uri $uri/index.html;
       access_log  off;
    }

    location /stub_status {
        stub_status on;
        allow 127.0.0.1;
        deny all;
        access_log   off;
    }

    location ~ ^/(status|ping)$ {
        access_log off;
        allow 127.0.0.1;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9000;
    }

    # Charset
    charset utf-8;

    # Logging
    access_log  /var/log/nginx/open.access.log  main;
    error_log   /var/log/nginx/open.error.log;

    # The X-XSS-Protection header is used by Internet Explorer
    add_header X-XSS-Protection "1; mode=block" always;

    # Force the latest IE version
    add_header "X-UA-Compatible" "IE=Edge";

    client_max_body_size 400M;

    root /var/www/YOURPROJECTDIRECTORY/html;

    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.html /index.php?$query_string;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ '\.php$|^/update.php' {
        proxy_set_header   Host             $host;
        proxy_set_header   X-Real-IP        $remote_addr;
        proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;

        include fastcgi_params;
        fastcgi_split_path_info ^(.+?\.php)(|/.*)$;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param QUERY_STRING $query_string;
        fastcgi_intercept_errors on;
        fastcgi_read_timeout 300;

        set $no_cache "";
        if ($request_method !~ ^(GET|HEAD)$) {
           set $no_cache "1";
        }

        if ($no_cache = "1") {
          add_header Set-Cookie "_mcnc=1; Max-Age=2; Path=/";
          add_header X-Microcachable "0";
        }

        if ($http_cookie ~ SESS) {
          set $no_cache "1";
        }

        # fastcgi_cache drupal;

        fastcgi_pass 127.0.0.1:9000;
    } 
```
Restart the web server with the new configuration,
```
	$ sudo systemctl restart nginx.service
```
	
### Install the OpenGov Drupal profile

The opengov profile can be installed via the interactive installer. Open up a web browser and navigate to localhost/,
follow the steps in the interactive installer, entering your credentials for the PostgreSQL server you created and
started in step 3 when prompted.
	
ALTERNATIVE INSTALL:
```
	$ sudo ./vendor/bin/drush site:install og
```


## Possible Issues

1. Composer, as well as the interactive installer, will want to create files but will not have the required permission
to do so. To fix this, navigate to your drupal project directory and run
```
	$ sudo chmod 777 -R ./
```
This will grant read and write permissions for every file and folder in your drupal project, and should be reverted
when your site is done installing.

2. Security-Enhance Linux(SELinux) is a Linux kernel security module that may interfere with your PostgreSQL server
and can be disabled by modifying /etc/selinux/config. Specifically, change the line SELINUX=enforcing to SELINUX=disabled.

3. If sufficient permissions are not granted, the GCWeb theme may not display correctly(the page only contains links and no CSS). To fix this,
navigate `Configuration->Development->Performance` and uncheck the options enabling aggregation of css and javascript files. 
Alternatively, you can disable css and js aggregation using:
``` 
    $ drush -y config-set system.performance css.preprocess 0
    $ drush -y config-set system.performance js.preprocess 0
```