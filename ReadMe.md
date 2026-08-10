# TLE Dockerized

## Initial install of TLE App

- Change into your installation directory and run:
  `git init`
  `git remote add origin git@github.com:The-Leadership-Experience/dockerapp.git`
  `git pull origin main`

  - Copy .env.example files
    `cp .env.example .env`

  - Open .env with vi or nano for editing
    `nano .env`

  - Copy settings.example.php file
    `cp src/elgg-config/settings.example.php src/elgg-config/settings.php`

  - Copy .htaccess.dist file
    `cp src/install/config/htaccess.dist src/.htaccess`

  - Open and edit .htaccess memory_limit, post_max_size and upload_max_filesize
    `nano src/.htaccess`

- Change into the 'src' directory,

  - If in development environment, run:
    `cd src && composer install --ignore-platform-reqs && cd ..`

  - Startup the containers for development:
    `docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build`

  - Customize localhost  for docker development

    *Open C:\Windows\System32\drivers\etc\hosts with administrative priviledge and map localhost to the custom host name for local development:*

    `127.0.0.1 v2.tleapp.local`

  - Go to <http://v2.tleapp.local:3000/install.php?step=requirements> and follow the installation steps.

- Edit the app source code files in src folder

## Shutting down and Running Web App

- To shutdown the app in development, cd to install directory in bash and run:
  `docker-compose -f docker-compose.yml -f docker-compose.dev.yml down`

- To check running app, run:
  `docker-compose ps`

- If the containers are not up in development, run:
  `docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build`

- Open the app container service in interactive mode:
  `docker-compose exec -it app bash`

- Create the **.log/app.log:** directory
  `mkdir .log && touch .log/app.log`

- Log app errors in realtime with timestamp to **.log/app.log:**
  `docker-compose logs -f -t app >> .log/app.log`

- Log database errors in realtime with timestamp to **.log/dbm.log:**
  `docker-compose logs -f -t dbm >> .log/dbm.log`

- To release back the terminal after running docker-compose logs, press 'CTRL + C'

## Using composer / elgg-cli and dataroot (change directory to src directory)

- List all available composer packages:
  `composer show`

- List all plugins:
  `docker-compose exec -w /var/www/html app php elgg-cli plugins:list`

- Install composer package
  `composer require vendor/package-name`

- Uninstall composer package
  `composer remove vendor/package-name`

- Install or move composer package to **require-dev** for development
  `composer require --dev vendor/package-name`

## Clear cache

  `docker-compose exec -w /var/www/dataroot app rm -rf caches/*`

## Upgrading Minor, patch-level upgrade

- **Back up everything:** Create a full backup of your Elgg database, your data directory, and your codebase.
- **Disable Caching:** Log in as an admin and disable all caching in Configure -> Settings -> Advanced Settings.
- **Optional - Disable Plugins:** To prevent plugin conflicts, you can create an empty file named *disabled* in your /mod/ directory

- From project root, run
  `composer self-update`
  `cd api`
  `composer require --with-all-dependencies elgg/elgg:6.3.3`
  `composer update`

- Login as admin user to run upgrade.

## Viewing phpmyadmin webUI

<http://v2.tleapp.local:3001/>

## Viewing mailhog webUI

<http://v2.tleapp.local:3002/>

## Deployment to production

- From project root, run:
    `git init`
    `git remote add origin git@github.com:The-Leadership-Experience/dockerapp.git`
    `git pull origin prod`

  - Copy .env.example file
    `cp .env.example .env`

    - Open and edit the variables with vi or nano
      `nano .env`

  - Copy settings.php file
    `cp src/elgg-config/settings.example.php src/elgg-config/settings.php`

  - Copy .htaccess file
    `cp src/install/config/htaccess.dist src/.htaccess`

  - Open and edit .htaccess memory_limit, post_max_size and upload_max_filesize
    `nano src/.htaccess`

  - Create nested directory for the app server block config file
    `sudo mkdir -p /etc/nginx/conf.d/users/tlebravado/the-leadership-experience.com/`

  - Copy the app configuration file to the nested directory
    `sudo cp app.conf.example /etc/nginx/conf.d/users/tlebravado/the-leadership-experience.com/app.conf`

    - Open and edit the port number (for the app service in docker-compose) with vi or nano
      `sudo nano /etc/nginx/conf.d/users/tlebravado/the-leadership-experience.com/app.conf`

    - Check for nginx syntax error and restart nginx
      `sudo nginx -t`

      - Restart nginx if check return successful
        `sudo systemctl restart nginx`

  - Change to 'api' directory and run:
    `cd api`
    `composer install --no-dev --ignore-platform-reqs`

    - Issue running composer with incompartible php version

      - which versions are actually installed on your server:
        `dnf list installed | grep php`

      - Run composer with compartible php version
        `/usr/local/bin/ea-php84 /usr/local/bin/composer install`

  - Change to install directory and startup the containers:
    `cd ..`
    `docker-compose up -d --build`

  - Go to <https://the-leadership-experience.com/install.php?step=requirements> and follow the installation steps. If your domain DNS was properly configured, the app will be served.

## Subsequent deployment to production

  `git pull origin prod`
  `cd api && composer install --no-dev`

- Default plugins are symlinked in docker-compose file, to refresh the symlinks, stop and start docker-compose
  `docker compose down`
  `docker compose up -d --build`

## FIX: Errors

- [ParseError] syntax error, unexpected 'protected' (T_PROTECTED), expecting variable (T_VARIABLE). **Solution: Run composer install with compartible php version.**
  - List installed php versions
    `dnf list installed | grep php`
  - Check location of compartible php version from the listed result
    `which ea-php84`
  - Run composer install using the version path
    `/usr/local/bin/ea-php84 /usr/local/bin/composer install --no-dev`

- RecursiveDirectoryIterator::__construct(/var/www/dataroot/caches/fastcache/boot_cache/Files/19/17): Failed to open directory: Permission denied
**Sometimes the boot_cache contains references to paths that no longer exist or are locked. Clear them directly through the running container:**
  `docker exec -u root -it app rm -rf /var/www/dataroot/caches/fastcache/boot_cache/Files/19/17`

***NEVER USE 'composer update' IN PRODUCTION ENVIRONMENT***
