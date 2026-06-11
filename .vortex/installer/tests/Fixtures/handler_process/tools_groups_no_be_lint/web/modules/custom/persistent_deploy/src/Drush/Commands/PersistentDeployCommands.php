@@ -69,7 +69,6 @@
    * @param \Consolidation\AnnotatedCommand\CommandData $command_data
    *   The command data.
    *
-   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
    */
   #[CLI\Hook(type: HookManager::PRE_COMMAND_HOOK, target: 'deploy:hook')]
   public function runPreDeploySteps(CommandData $command_data): void {
@@ -84,7 +83,6 @@
    * @param \Consolidation\AnnotatedCommand\CommandData $command_data
    *   The command data.
    *
-   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
    */
   #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: 'deploy:hook')]
   public function runPostDeploySteps(mixed $result, CommandData $command_data): void {
