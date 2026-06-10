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
