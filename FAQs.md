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
1. Run `ahoy debug`
2. Enable listening for incoming debug connections in your IDE.
3. If required, provide server URL to your IDE as it appears in the browser: 
   `http://your-site.docker.amazee.io`
4. Enable Xdebug flag in the request coming from your web browser (use one of 
   the extensions or add `?XDEBUG_SESSION_START=1` to your URL).
5. Set a breakpoint in your IDE and perform a request in the web browser.

Use the same commands to debug CLI scripts.  

Use `ahoy up` to restart the stack without Xdebug enabled.
