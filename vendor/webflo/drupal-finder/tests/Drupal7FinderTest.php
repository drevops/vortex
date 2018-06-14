<?php

namespace DrupalFinder\Tests;

use org\bovigo\vfs\vfsStream;

class Drupal7FinderTest extends DrupalFinderTestBase
{
    /**
     * @var \DrupalFinder\DrupalFinder
     */
    protected $finder;

    protected static $fileStructure = [
        'includes' => [
            'common.inc' => '',
        ],
        'misc' => [
            'drupal.js' => '',
        ],
        'sites' => [
            'all' => [
                'modules' => []
            ]
        ]
    ];

    /**
     * @return array
     */
    protected function getDrupalComposerStructure()
    {
        $fileStructure = [
            'web' => static::$fileStructure,
            'composer.json' => [
                'require' => [
                    'drupal/drupal' => '*',
                ],
                'extra' => [
                    'installer-paths' => [
                        'web/' => [
                            'type:drupal-core',
                        ],
                    ],
                ],
            ],
            'vendor' => [],
        ];
        return $fileStructure;
    }

    public function testDrupalComposerStructure()
    {
        $fileStructure = $this->getDrupalComposerStructure();
        $this->assertComposerStructure($fileStructure);
    }

    public function testDrupalComposerStructureWithoutRequire()
    {
        $fileStructure = [
            'web' => static::$fileStructure,
            'composer.json' => [
                'extra' => [
                    'installer-paths' => [
                        'web' => [
                            'drupal/drupal',
                        ],
                    ],
                ],
            ],
        ];
        $this->assertComposerStructure($fileStructure);
    }

    public function testNoDrupalRootWithRealFilesystem()
    {
        $root = $this->tempdir(sys_get_temp_dir());

        $this->assertFalse($this->finder->locateRoot($root));
        $this->assertFalse($this->finder->getDrupalRoot());
        $this->assertFalse($this->finder->getComposerRoot());
        $this->assertFalse($this->finder->getVendorDir());
    }

    public function testDrupalComposerStructureWithRealFilesystem()
    {
        $root = $this->tempdir(sys_get_temp_dir());
        $this->dumpToFileSystem($this->getDrupalComposerStructure(), $root);

        $this->assertTrue($this->finder->locateRoot($root));
        $this->assertSame($root . '/web', $this->finder->getDrupalRoot());
        $this->assertSame($root, $this->finder->getComposerRoot());
        $this->assertSame($root . '/vendor', $this->finder->getVendorDir());

        // Test symlink implementation
        $symlink = $this->tempdir(sys_get_temp_dir());
        $this->symlink($root, $symlink . '/foo');

        $this->assertTrue($this->finder->locateRoot($symlink . '/foo'));
        $this->assertSame($root . '/web', $this->finder->getDrupalRoot());
        $this->assertSame($root, $this->finder->getComposerRoot());
        $this->assertSame($root . '/vendor', $this->finder->getVendorDir());
    }

    public function testDrupalWithLinkedModule()
    {
        $root = $this->tempdir(sys_get_temp_dir());
        $this->dumpToFileSystem($this->getDrupalComposerStructure(), $root);

        $module = $this->tempdir(sys_get_temp_dir());
        $module_link = $root . '/web/sites/all/modules/foo';
        $this->symlink($module, $module_link);

        $this->assertTrue($this->finder->locateRoot($module_link));
        $this->assertSame($root . '/web', realpath($this->finder->getDrupalRoot()));
        $this->assertSame($root, realpath($this->finder->getComposerRoot()));
        $this->assertSame($root . '/vendor', realpath($this->finder->getVendorDir()));
    }

    public function testDrupalWithCustomVendor()
    {
        $root = $this->tempdir(sys_get_temp_dir());
        $fileStructure = $this->getDrupalComposerStructure();
        $composerJson = $fileStructure['composer.json'];
        $composerJson['config']['vendor-dir'] = 'vendor-foo';
        $fileStructure['composer.json'] = $composerJson;
        $fileStructure['vendor-foo'] = [];
        $this->dumpToFileSystem($fileStructure, $root);

        $this->assertTrue($this->finder->locateRoot($root));
        $this->assertSame($root . '/web', realpath($this->finder->getDrupalRoot()));
        $this->assertSame($root, realpath($this->finder->getComposerRoot()));
        $this->assertSame($root . '/vendor-foo', realpath($this->finder->getVendorDir()));
    }

    /**
     * @param $fileStructure
     */
    protected function assertComposerStructure($fileStructure)
    {
        $fileStructure = $this->prepareFileStructure($fileStructure);
        $root = vfsStream::setup('root', null, $fileStructure);
        $this->assertTrue($this->finder->locateRoot($root->url() . '/web'));
        $this->assertSame('vfs://root/web', $this->finder->getDrupalRoot());
        $this->assertSame('vfs://root', $this->finder->getComposerRoot());
        $this->assertSame('vfs://root/vendor', $this->finder->getVendorDir());

        $this->assertTrue($this->finder->locateRoot($root->url() . '/web/misc'));
        $this->assertSame('vfs://root/web', $this->finder->getDrupalRoot());
        $this->assertSame('vfs://root', $this->finder->getComposerRoot());
        $this->assertSame('vfs://root/vendor', $this->finder->getVendorDir());

        $this->assertTrue($this->finder->locateRoot($root->url()));
        $this->assertSame('vfs://root/web', $this->finder->getDrupalRoot());
        $this->assertSame('vfs://root', $this->finder->getComposerRoot());
        $this->assertSame('vfs://root/vendor', $this->finder->getVendorDir());

        $root = vfsStream::setup(
            'root',
            null,
            ['nested_folder' => $fileStructure]
        );
        $this->assertFalse($this->finder->locateRoot($root->url()));
        $this->assertFalse($this->finder->getDrupalRoot());
        $this->assertFalse($this->finder->getComposerRoot());
        $this->assertFalse($this->finder->getVendorDir());
    }
}
