@@ -81,7 +81,6 @@
    * @param \Consolidation\AnnotatedCommand\CommandData $command_data
    *   The command data.
    *
-   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
    */
   #[CLI\Hook(type: HookManager::PRE_COMMAND_HOOK, target: 'deploy:hook')]
   public function preDeploy(CommandData $command_data): void {
@@ -100,7 +99,6 @@
    * @param \Consolidation\AnnotatedCommand\CommandData $command_data
    *   The command data.
    *
-   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
    */
   #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: 'deploy:hook')]
   public function postDeploy(mixed $result, CommandData $command_data): void {
@@ -178,7 +176,6 @@
    *   One of the ENVIRONMENT_* values (local, ci, dev, stage, prod) or an empty
    *   string when not set.
    *
-   * @SuppressWarnings("PHPMD.StaticAccess")
    */
   protected function environment(): string {
     return (string) Settings::get('environment', '');
