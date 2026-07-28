Acquia Cloud Hooks
==================

Cloud Hooks is a feature of Acquia Cloud, the Drupal cloud hosting platform.
For more information, see https://www.acquia.com/products-services/acquia-dev-cloud.

Note that hook scripts must have the Unix "executable" bit in order to run.

Also, event directories can (and should) be symlinked to other event directories
to guarantee consistency of operations.

File structure
--------------
`library` - universal scripts that can be ran within any environment. These
            scripts are symlinked into per-environment directory.

`common` - scripts that run for all environments, including on-demand environments.

`dev`, `test`, `prod` - scripts that run within each specific environment.

Running order
-------------
For any hook event (`post-code-deploy`, `post-code-update`, `post-db-copy` etc.)
all scripts in `common` run first and then all scripts in per-environment
directory run.
