@@ -54,7 +54,6 @@
    *   One of the ENVIRONMENT_* values (local, ci, dev, stage, prod) or an empty
    *   string when not set.
    *
-   * @SuppressWarnings("PHPMD.StaticAccess")
    */
   protected function environment(): string {
     return (string) Settings::get('environment', '');
@@ -95,7 +94,6 @@
    * @return \Consolidation\SiteProcess\SiteProcess
    *   The completed process.
    *
-   * @SuppressWarnings("PHPMD.StaticAccess")
    */
   protected function drush(string $command, array $args = [], array $options = []): SiteProcess {
     $process = Drush::drush(Drush::aliasManager()->getSelf(), $command, $args, $options + Drush::redispatchOptions());
