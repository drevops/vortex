<?php

namespace DrevOps\Installer\Command;

use DrevOps\Installer\Bag\AbstractBag;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\InstallManager;
use DrevOps\Installer\PrintManager;
use DrevOps\Installer\Prompt\Concrete\DatabaseDownloadSourcePrompt;
use DrevOps\Installer\Prompt\Concrete\DatabaseImagePrompt;
use DrevOps\Installer\Prompt\Concrete\DatabaseStoreTypePrompt;
use DrevOps\Installer\Prompt\Concrete\DeployTypePrompt;
use DrevOps\Installer\Prompt\Concrete\MachineNamePrompt;
use DrevOps\Installer\Prompt\Concrete\ModulePrefixPrompt;
use DrevOps\Installer\Prompt\Concrete\NamePrompt;
use DrevOps\Installer\Prompt\Concrete\OrgMachineNamePrompt;
use DrevOps\Installer\Prompt\Concrete\OrgPrompt;
use DrevOps\Installer\Prompt\Concrete\OverrideExistingDbPrompt;
use DrevOps\Installer\Prompt\Concrete\PreserveAcquiaPrompt;
use DrevOps\Installer\Prompt\Concrete\PreserveDocCommentsPrompt;
use DrevOps\Installer\Prompt\Concrete\PreserveDrevopsInfoPrompt;
use DrevOps\Installer\Prompt\Concrete\PreserveFtpPrompt;
use DrevOps\Installer\Prompt\Concrete\PreserveLagoonPrompt;
use DrevOps\Installer\Prompt\Concrete\PreserveRenovatebotPrompt;
use DrevOps\Installer\Prompt\Concrete\ProceedDrevopsInstallPrompt;
use DrevOps\Installer\Prompt\Concrete\ProfilePrompt;
use DrevOps\Installer\Prompt\Concrete\ProvisionUseProfilePrompt;
use DrevOps\Installer\Prompt\Concrete\ThemePrompt;
use DrevOps\Installer\Prompt\Concrete\UrlPrompt;
use DrevOps\Installer\Prompt\Concrete\WebrootPrompt;
use DrevOps\Installer\Prompt\PromptManager;
use DrevOps\Installer\Utils\Env;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Run command.
 *
 * Install command.
 *
 * @package DrevOps\Installer\Command
 */
class InstallCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'install';

  /**
   * @var \DrevOps\Installer\Bag\Config
   */
  protected $config;

  /**
   * @var \DrevOps\Installer\Prompt\PromptManager
   */
  protected $questionManager;

  /**
   * @var \DrevOps\Installer\InstallManager
   */
  protected $installManager;

  /**
   * @var \DrevOps\Installer\PrintManager
   */
  protected $printManager;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->addArgument('path', InputArgument::OPTIONAL, 'Destination directory. Optional. Defaults to the current directory');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->config = Config::getInstance()->fromValues(['cwd' => getcwd()] + $input->getArguments() + $input->getOptions());

    $io = $this->initIo($input, $output);
    $this->questionManager = new PromptManager($io, $this->config);
    $this->printManager = new PrintManager($io, $this->config);

    return $this->doExecute();
  }

  /**
   * Execute command's business logic.
   *
   * @return int
   *   Command exit code.
   */
  protected function doExecute(): int {
    $this->printManager->printHeader();

    $answers = $this->askQuestions();
    $this->config->fromValues($answers->getAll());
    // Lock config to prevent further changes.
    $this->config->setReadOnly();

    if ($this->askShouldProceed()) {
      // $this->installManager->install($this->config);
      $this->printManager->printFooter();
    }
    else {
      $this->printManager->printAbort();
    }

    return self::SUCCESS;
  }

  protected function askQuestions(): AbstractBag {
    $this->questionManager->askQuestions(function (PromptManager $qm): void {
      $is_installed = InstallManager::isInstalled($this->config->getDstDir());

      // // @todo remove this.
      // $qm->ask(DatabaseDownloadSourceQuestion::ID);
      // $qm->ask(DatabaseStoreTypeQuestion::ID);
      //      $qm->ask(DeployTypeQuestion::ID);
      //      print_r($qm->getAnswers()->getAll());
      // exit (1);
      // For already installed projects, we do not need to ask for the webroot.
      if ($is_installed) {
        // @todo Implement this.
        $qm->ask(WebrootPrompt::ID, TRUE);
      }

      $qm->ask(NamePrompt::ID);
      $qm->ask(MachineNamePrompt::ID);
      $qm->ask(OrgPrompt::ID);
      $qm->ask(OrgMachineNamePrompt::ID);
      $qm->ask(ModulePrefixPrompt::ID);
      $qm->ask(ProfilePrompt::ID);
      $qm->ask(ThemePrompt::ID);
      $qm->ask(UrlPrompt::ID);

      if ($is_installed) {
        $qm->ask(WebrootPrompt::ID);
      }

      $qm->ask(ProvisionUseProfilePrompt::ID);

      if ($qm->getAnswer(ProvisionUseProfilePrompt::ID)) {
        $qm->setAnswer(DatabaseDownloadSourcePrompt::ID, DatabaseDownloadSourcePrompt::CHOICE_NONE);
        $qm->setAnswer(DatabaseImagePrompt::ID, '');
      }
      else {
        $qm->ask(DatabaseDownloadSourcePrompt::ID);

        if ($qm->getAnswer(DatabaseDownloadSourcePrompt::ID) != DatabaseDownloadSourcePrompt::CHOICE_DOCKER_REGISTRY) {
          // Note that "database_store_type" is a pseudo-answer - it is only used
          // to improve UX and is not exposed as a variable (although, it has
          // default, discovery and normalisation callbacks).
          $qm->ask(DatabaseStoreTypePrompt::ID);
        }

        if ($qm->getAnswer(DatabaseStoreTypePrompt::ID) == DatabaseStoreTypePrompt::CHOICE_FILE) {
          $qm->setAnswer(DatabaseImagePrompt::ID, '');
        }
        else {
          $qm->ask(DatabaseImagePrompt::ID);
        }
      }

      $qm->ask(OverrideExistingDbPrompt::ID);

      $qm->ask(DeployTypePrompt::ID);

      if ($qm->getAnswer(DatabaseDownloadSourcePrompt::ID) != DatabaseDownloadSourcePrompt::CHOICE_FTP) {
        $qm->ask(PreserveFtpPrompt::ID);
      }
      else {
        $qm->setAnswer(PreserveFtpPrompt::ID, TRUE);
      }

      if ($qm->getAnswer(DatabaseDownloadSourcePrompt::ID) != DatabaseDownloadSourcePrompt::CHOICE_ACQUIA_BACKUP) {
        $qm->ask(PreserveAcquiaPrompt::ID);
      }
      else {
        $qm->setAnswer(PreserveAcquiaPrompt::ID, TRUE);
      }

      $qm->ask(PreserveLagoonPrompt::ID);

      $qm->ask(PreserveRenovatebotPrompt::ID);

      $qm->ask(PreserveDocCommentsPrompt::ID);
      $qm->ask(PreserveDrevopsInfoPrompt::ID);
    });

    if (!$this->config->isQuiet()) {
      $summary = $this->questionManager->getAnswersSummary();
      $this->printManager->printSummary($summary, 'INSTALLATION SUMMARY');
    }

    return $this->questionManager->getAnswers();
  }

  protected function askShouldProceed() {
    $proceed = TRUE;

    if (!$this->config->isQuiet()) {
      $proceed = $this->questionManager->ask(ProceedDrevopsInstallPrompt::ID);
    }

    // Kill-switch to not proceed with install. If false, the installation will
    // not proceed despite the answer received above.
    if (!$this->config->get(Env::INSTALLER_INSTALL_PROCEED)) {
      $proceed = FALSE;
    }

    return $proceed;
  }

  /**
   * Initialise output.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output.
   */
  protected function initIo(InputInterface $input, OutputInterface $output): SymfonyStyle {
    // Add support for bold.
    $boldStyle = new OutputFormatterStyle(NULL, NULL, ['bold']);
    $output->getFormatter()->setStyle('bold', $boldStyle);
    if ($output instanceof ConsoleOutputInterface) {
      $output = $output->getErrorOutput();
      $output->getFormatter()->setStyle('bold', $boldStyle);
    }

    return new SymfonyStyle($input, $output);
  }

}
