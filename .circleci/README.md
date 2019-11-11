Scripts in this directory are used to run jobs in CI. They are simple wrappers 
around workflow commands used to compensate for CI limitations.
 
Jobs are extracted into standalone scripts in order to allow to alter jobs
without altering CI configuration. This allows to update stack with newer 
versions.
