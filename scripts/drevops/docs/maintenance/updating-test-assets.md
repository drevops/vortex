# Updating test assets

## Updating demo database

1. Run fresh build of DrevOps locally:

       echo "DREVOPS_DRUPAL_PROFILE=standard">>.env.local
       echo "DREVOPS_DRUPAL_INSTALL_FROM_PROFILE=1">>.env.local
       rm .data/db.sql
       DREVOPS_AHOY_CONFIRM_RESPONSE=1 ahoy build

2. Check that everything looks correctly on the site
3. Export DB

        ahoy export-db

4. Make sure that exported DB does not have data in `cache_*` and `watchdog` tables
5. Upload DB to https://github.com/drevops/drevops/wiki as a test file (`db.distN.sql`)
6. Update references in code from `db.demo.sql` to `db.distN.sql`
7. Run CI build
8. Revert updated references to `db.demo.sql`
9. Update `db.demo.sql` in Wiki
10. Merge branch to `main`.
11. Wait for CI to pass.
12. Remove `db.distN.sql` from Wiki.
