## A word about the MultiTenant Branch

Changed files:

### .env.example
* added "SECRET_KEY"

### app.json
* modified to reflect the new .env variable

### config/auth.php
* added ```config('auth.secret_key')```

### di/config.php
* hacky, but modified Client::class to select the right database after a quick reading from 0

### src/Pho/Stream/Authorization.php
* Where real magic happens. App key/secret matching.

### src/Pho/Stream/Controller/FeedController.php
*  feedExists no longer checked in follow

