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

## How to run Livereload?
1. Set `$settings['livereload'] = TRUE;` in `settings.local.php` file
2. Clear drupal cache `ahoy drush cr`
3. Run: `npm run watch`

Note: Watching works only from docker host.

## How to debug Behat tests?
```
./scripts/xdebug.sh vendor/bin/behat path/to/file
```
