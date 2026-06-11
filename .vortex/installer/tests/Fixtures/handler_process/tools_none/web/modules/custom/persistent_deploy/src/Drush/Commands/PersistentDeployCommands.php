@@ -68,7 +68,6 @@
    * @param \Consolidation\AnnotatedCommand\CommandData $command_data
    *   The command data.
    *
-   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
    */
   #[CLI\Hook(type: HookManager::PRE_COMMAND_HOOK, target: 'deploy:hook')]
   public function runPreDeploySteps(CommandData $command_data): void {
@@ -83,7 +82,6 @@
    * @param \Consolidation\AnnotatedCommand\CommandData $command_data
    *   The command data.
    *
-   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
    */
   #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: 'deploy:hook')]
   public function runPostDeploySteps(mixed $result, CommandData $command_data): void {
