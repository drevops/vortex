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
ahoy drush cc all
```

## How to login to Drupal site?
```
ahoy login
```

## How to run Livereload?
1. Set `$conf['livereload'] = TRUE;` in `settings.local.php` file
2. Clear drupal cache `ahoy drush cc all`
3. Run: `ahoy few`

## How to use Xdebug?
1. Uncomment this line in `docker-compose.yml`:
  ```
  #XDEBUG_ENABLE: "true"
  ```
2. Restart the stack: `ahoy up`.
3. Enable listening for incoming debug connections in your IDE.
4. If required, provide server URL to your IDE as it appears in the browser: `http://your-site.docker.amazee.io`
5. Enable Xdebug flag in the request coming from your web browser.
6. Set a breakpoint in your IDE and perform a request in the web browser. 

## How to debug Behat tests?
1. Uncomment this line in `docker-compose.yml`:
  ```
  #XDEBUG_ENABLE: "true"
  ```
2. Restart the stack: `ahoy up`.
3. Enable listening for incoming debug connections in your IDE.
4. Set a breakpoint in your IDE and perform a request in the web browser.
5. SSH into CLI container: `ahoy cli`
6. Run test: 
  ```
  ./scripts/xdebug.sh vendor/bin/behat path/to/file
  ```
