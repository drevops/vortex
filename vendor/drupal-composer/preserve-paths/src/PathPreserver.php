<?php

/**
 * Contains \derhasi\Composer\PathPreserver
 */

namespace derhasi\Composer;

/**
 * Class PathPreserver
 */
class PathPreserver
{

    /**
     * Temporary file permission to allow moving protected paths.
     */
    const FILEPERM = 0755;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var string[]
     */
    protected $installPaths;

    /**
     * @var string[]
     */
    protected $preservePaths;

    /**
     * @var \Composer\Util\FileSystem
     */
    protected $filesystem;

    /**
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    /**
     * @var string[string]
     */
    protected $backups = array();

    /**
     * @var string[string]
     */
    protected $filepermissions = array();

    /**
     * Constructor.
     *
     * @param string[]                  $installPaths
     *   Array of install paths (must be absolute)
     * @param string[]                  $preservePaths
     *   Array of preservable paths (must be absolute)
     * @param string                    $cacheDir
     *   Absolute path to composer cache dir.
     * @param \Composer\Util\FileSystem $filesystem
     *   The filesystem provided by composer to work with.
     * @param \Composer\IO\IOInterface  $io
     *   IO interface for writing messages.
     */
    public function __construct($installPaths, $preservePaths, $cacheDir, \Composer\Util\FileSystem $filesystem, \Composer\IO\IOInterface $io)
    {
        $this->installPaths = array_unique($installPaths);
        $this->preservePaths = array_unique($preservePaths);
        $this->filesystem = $filesystem;
        $this->cacheDir = $cacheDir;
        $this->io = $io;
    }

    /**
     * Backs up the paths.
     */
    public function preserve()
    {

        foreach ($this->installPaths as $installPath) {
            $installPathNormalized = $this->filesystem->normalizePath($installPath);

            // Check if any path may be affected by modifying the install path.
            $relevantPaths = array();
            foreach ($this->preservePaths as $path) {
                $normalizedPath = $this->filesystem->normalizePath($path);
                if (static::fileExists($path) && strpos($normalizedPath, $installPathNormalized) === 0) {
                    $relevantPaths[] = $normalizedPath;
                }
            }

            // If no paths need to be backed up, we simply proceed.
            if (empty($relevantPaths)) {
                continue;
            }

            $unique = $installPath.' '.time();
            $cacheRoot = $this->filesystem->normalizePath($this->cacheDir.'/preserve-paths/'.sha1($unique));
            $this->filesystem->ensureDirectoryExists($cacheRoot);

            // Before we back paths up, we need to make sure, permissions are
            // sufficient to that task.
            $this->preparePathPermissions($relevantPaths);

            foreach ($relevantPaths as $original) {
                $backupLocation = $cacheRoot.'/'.sha1($original);
                $this->filesystem->rename($original, $backupLocation);
                $this->backups[$original] = $backupLocation;
            }
        }
    }

    /**
     * Restore previously backed up paths.
     *
     * @see PathPreserver::backupSubpaths()
     */
    public function rollback()
    {
        if (empty($this->backups)) {
            return;
        }

        foreach ($this->backups as $original => $backupLocation) {
            // Remove any code that was placed by the package at the place of
            // the original path.
            if (static::fileExists($original)) {
                if (is_dir($original)) {
                    $this->filesystem->emptyDirectory($original, false);
                    $this->filesystem->removeDirectory($original);
                } else {
                    $this->filesystem->remove($original);
                }

                $this->io->write(sprintf('<comment>Files of installed package were overwritten with preserved path %s!</comment>', $original), true);
            }

            $folder = dirname($original);
            $this->filesystem->ensureDirectoryExists($folder);
            // Make sure we can write the file to the folder.
            $this->makePathWritable($folder);
            $this->filesystem->rename($backupLocation, $original);

            if ($this->filesystem->isDirEmpty(dirname($backupLocation))) {
                $this->filesystem->removeDirectory(dirname($backupLocation));
            }
        }

        // Restore all path permissions, that where set for the sake of moving
        // things around.
        $this->restorePathPermissions();

        // With a clean array, we can start over.
        $this->backups = array();
    }

    /**
     * Check if file really exists.
     *
     * As php can only determine, whether a file or folder exists when the parent
     * directory is executable, we need to provide a workaround.
     *
     * @param string $path
     *   The path as in file_exists()
     *
     * @return bool
     *   Returns TRUE if file exists, like in file_exists(),
     *   but without restriction.
     *
     * @see file_exists()
     */
    public static function fileExists($path)
    {

      // Get all parent directories.
        $folders = array();
        $resetPerms = array();
        $folder = $path;
        while ($folder = dirname($folder)) {
            if ($folder === '.' || $folder === '/' || preg_match("/^.:\\\\$/", $folder)) {
                break;
            } elseif ($folder === '') {
                continue;
            }
            $folders[] = $folder;
        }

        foreach (array_reverse($folders) as $currentFolder) {
            // In the case a parent folder does not exist, the file cannot exist.
            if (!is_dir($currentFolder)) {
                $return = false;
                break;
            } // In the case the folder is really a folder, but not executable, we need
            // to change that, so we can check if the file really exists.
            elseif (!is_executable($currentFolder)) {
                $resetPerms[$currentFolder] = fileperms($currentFolder);
                chmod($currentFolder, 0755);
            }
        }

        if (!isset($return)) {
            $return = file_exists($path);
        }

        // Reset permissions in reverse order.
        foreach (array_reverse($resetPerms, true) as $folder => $mode) {
            chmod($folder, $mode);
        }

        return $return;
    }

    /**
     * Prepares source paths for backup.
     *
     * @param $paths
     *
     * @see PathPreserver::restorePathPermissions()
     */
    protected function preparePathPermissions($paths)
    {
        foreach ($paths as $path) {
            // In the case the path or its parent is not writable, we cannot move the
            // path. Therefore we change the permissions temporarily and restore them
            // later.
            if (!is_writable($path)) {
                $this->makePathWritable($path);
            }

            $parent = dirname($path);
            if (!is_writable($parent)) {
                $this->makePathWritable($parent);
            }
        }
    }

    /**
     * Helper to make path writable.
     *
     * @param string $path
     */
    protected function makePathWritable($path)
    {
        // Make parent writable, before we can change the path itself.
        $parent = dirname($path);
        if ($parent != '.' && !is_writable($parent)) {
            $this->makePathWritable($parent);
        }

        $this->filepermissions[$path] = fileperms($path);
        chmod($path, static::FILEPERM);
    }

    /**
     * Restores path permissions that have been changed before.
     *
     * @see PathPreserver::preparePathPermissions()
     */
    protected function restorePathPermissions()
    {
        // We restore child permissions first.
        arsort($this->filepermissions);

        foreach ($this->filepermissions as $path => $perm) {
            chmod($path, $perm);
        }
    }
}
