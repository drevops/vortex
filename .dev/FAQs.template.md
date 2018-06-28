#FAQs

## How to know which commands are available?
```
make help
```

## How to pass CLI arguments to commands?
```
make mycommand -- myarg1 myarg2 --myoption1 --myoption2=myvalue
```

## How to clear Drupal cache?
```
make drush -- cc all
```

## How to login to Drupal site?
```
make login
```

## How to run Livereload?
1. Set `$conf['livereload'] = TRUE;` in `settings.local.php` file
2. Clear drupal cache `make drush cc all`
3. Run: `npm run watch`

Note: Watching works only from docker host.

## How to debug Behat tests?
```
./scripts/xdebug.sh vendor/bin/behat path/to/file
```
