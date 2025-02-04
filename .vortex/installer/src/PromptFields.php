<?php

declare(strict_types=1);

namespace DrevOps\Installer;

interface PromptFields {

  public const NAME = 'name';

  public const MACHINE_NAME = 'machine_name';

  public const ORG = 'org';

  public const ORG_MACHINE_NAME = 'org_machine_name';

  public const DOMAIN = 'domain';

  public const CODE_PROVIDER = 'code_provider';

  public const GITHUB_TOKEN = 'github_token';

  public const GITHUB_REPO = 'github_repo';

  public const USE_CUSTOM_PROFILE = 'use_custom_profile';

  public const PROFILE = 'profile';

  public const MODULE_PREFIX = 'module_prefix';

  public const THEME = 'theme';

  public const HOSTING_PROVIDER = 'hosting_provider';

  public const WEBROOT_CUSTOM = 'webroot_custom';

  public const DEPLOY_TYPE = 'deploy_type';

  public const PROVISION_TYPE = 'provision_type';

  public const DATABASE_DOWNLOAD_SOURCE = 'database_download_source';

  public const DATABASE_STORE_TYPE = 'database_store_type';

  public const CI_PROVIDER = 'ci_provider';

  public const DEPENDENCY_UPDATES_PROVIDER = 'dependency_updates_provider';

  public const ASSIGN_AUTHOR_PR = 'assign_author_pr';

  public const LABEL_MERGE_CONFLICTS_PR = 'label_merge_conflicts_pr';

  public const PRESERVE_PROJECT_DOCS = 'preserve_project_docs';

  public const PRESERVE_ONBOARDING = 'preserve_onboarding';

}
