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
3. Run: `ahoy few`

## How to use Xdebug?
1. Run `ahoy debug`
2. Enable listening for incoming debug connections in your IDE.
3. If required, provide server URL to your IDE as it appears in the browser: `http://your-site.docker.amazee.io`
4. Enable Xdebug flag in the request coming from your web browser.
5. Set a breakpoint in your IDE and perform a request in the web browser. 

Use `ahoy up` to restart the stack without Xdebug enabled.

## How to debug Behat tests?
1. Run `ahoy debug`
2. Enable listening for incoming debug connections in your IDE.
3. Set a breakpoint in your IDE and perform a request in the web browser.
4. SSH into CLI container: `ahoy cli`
5. Run test: 
  ```
  ./scripts/xdebug.sh vendor/bin/behat path/to/file
  ```
