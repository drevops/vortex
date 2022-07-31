# Maintaining DrevOps

This section will contain information required to maintain DrevOps, including
patterns used, commands and other architectural explanation.

## Updating demo database

### Dump update

1. Run fresh build of DrevOps locally:

       echo "DREVOPS_DRUPAL_PROFILE=standard">>.env.local
       echo "DREVOPS_DRUPAL_INSTALL_FROM_PROFILE=1">>.env.local
       rm .data/db.sql
       DREVOPS_AHOY_CONFIRM_RESPONSE=1 ahoy build

2. Check that everything looks correctly on the site
3. Export DB

        ahoy export-db

4. Make sure that exported DB does not have data in `cache_*` and `watchdog` tables
5. Upload DB to https://github.com/drevops/drevops/wiki as a test file (`db_d9.distN.sql`)
6. Update refernces in code from `db_d9.dist.sql` to `db_d9.distN.sql`
7. Run CI build
8. Revert updated references to `db_d9.dist.sql`
9. Update `db_d9.dist.sql` in Wiki
10. Merge branch to `9.x`.
11. Wait for CI to pass.
12. Remove `db_d9.distN.sql` from Wiki.
