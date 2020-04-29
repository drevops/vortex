# Marketing website for DrevOps
Drupal website template with integrations

https://www.drevops.com

# WAIT! DO NOT GO! This is NOT the code branch! Use one of the branches below.

[Click here to switch to Drupal 8 version](https://github.com/drevops/drevops/tree/8.x)

[Click here to switch to Drupal 7 version](https://github.com/drevops/drevops/tree/7.x)

## Maintenance of marketing website

The page is published automatically once changes are pushed.

### Compile site locally

https://help.github.com/en/enterprise/2.14/user/articles/setting-up-your-github-pages-site-locally-with-jekyll

1. Install Ruby
2. Install Bundler
   ``` 
    gem install bundler
   ```
3. Build and serve site:
   ```
   cd docs
   bundle install
   bundle exec jekyll serve
   ```
4. Access site at http://127.0.0.1:4000/

### Check spelling.

Run `./scripts/check-spell.sh`
