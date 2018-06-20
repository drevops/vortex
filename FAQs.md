#FAQs

## Why `Makefile`?
- Consistent commands across projects.  
- Standalone file that can be easily copied across projects.
- Works on all *nix systems.
- Does not require additional language or package installation.
- Workflow is no longer captured in places that were not designed for it: Composer scripts, NPM scripts etc. 

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

# Why are `amazeeio` containers used?
- Amazee.io maintain their containers as they are powering their open-source hosting platform Lagoon.
- Changes to containers are fully tested with every change using CI systems (part of Lagoon). 
