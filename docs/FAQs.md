# FAQs

## How to know which commands are available?
```
ahoy help
```

## How to pass CLI arguments to commands?
```
ahoy mycommand -- myarg1 myarg2 --myoption1 --myoption2=myvalue
```

## How to clear Drupal cache?
```
ahoy drush -- cr
```

## How to login to Drupal site?
```
ahoy login
```

## How to connect to the database?
1. Run `ahoy info` and grab the DB host port number.
2. Use these connection details:
  - Host: `127.0.0.1`
  - Username: `drupal`
  - Password: `drupal`
  - Database: `drupal`
  - Port: the port from step 1

## How to run Livereload?
1. If `settings.local.php` does not exist, copy `default.settings.local.php` to `settings.local.php`
2. Set `$settings['livereload'] = TRUE;` in `settings.local.php` file
3. Clear drupal cache `ahoy drush cr`
4. Run: `ahoy few`

## How to use Xdebug?
1. Run `ahoy debug`
2. Enable listening for incoming debug connections in your IDE.
3. If required, provide server URL to your IDE as it appears in the browser.
4. Enable Xdebug flag in the request coming from your web browser (use one of
   the extensions or add `?XDEBUG_SESSION_START=1` to your URL).
5. Set a breakpoint in your IDE and perform a request in the web browser.

Use the same commands to debug CLI scripts.

Use `ahoy up` to restart the stack without Xdebug enabled.

## How to use Xdebug on Behat scripts?
1. Enable debugging: `ahoy debug`
2. Enter CLI container: `ahoy cli`
3. Run Behat tests:
   ```
   vendor/bin/behat path/to/test.feature
   ```

## What should I do to switch to a "clean" branch environment?
Provided that your stack is already running:
1. Switch to your branch
2. `composer install`
3. `ahoy site-install`

Note that you do not need to rebuild the full stack using `ahoy build` every time.
However, sometimes you would want to have an absolutely clean environment - in that
case, use `ahoy build`.

## How to just import the database?
Provided that your stack is already running:
`ahoy drush sql-drop -y; ahoy drush sql-cli < .data/db.sql`

## How to add Drupal modules

`composer require drupal/module_name`

## Adding patches for Drupal modules

1. Add `title` and `url` to patch on https://drupal.org to the `patches` array in `extra` section in `composer.json`.

```
    "extra": {
        "patches": {
            "drupal/core": {
                "Contextual links should not be added inside another link - https://www.drupal.org/node/2898875": "https://www.drupal.org/files/issues/contextual_links_should-2898875-3.patch"
            }
        }
    }
```

2. `composer update --lock`
