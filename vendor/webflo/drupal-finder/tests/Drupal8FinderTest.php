<?php

namespace DrupalFinder\Tests;

use org\bovigo\vfs\vfsStream;

class Drupal8FinderTest extends DrupalFinderTestBase
{
    protected static $fileStructure = [
        'autoload.php' => '',
        'composer.json' => [
            'extra' => [
                'installer-paths' => [
                    'core' => [
                        'type:drupal-core'
                    ]
                ]
            ]
        ],
        'core' => [
            'includes' => [
                'common.inc' => '',
            ],
            'misc' => [
                'drupal.js' => '',
            ],
            'core.services.yml' => '',
        ],
        'modules' => [],
        'vendor' => [],
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
                    'drupal/core' => '*',
                ],
                'extra' => [
                    'installer-paths' => [
                        'web/core' => [
                            'type:drupal-core',
                        ],
                    ],
                ],
            ],
            'vendor' => [],
        ];
        unset($fileStructure['web']['composer.json']);
        unset($fileStructure['web']['vendor']);

        return $fileStructure;
    }

    protected function setUp()
    {
        parent::setUp();
        $this->finder = new \DrupalFinder\DrupalFinder();
    }

    public function testDrupalDefaultStructure()
    {
        $root = vfsStream::setup('root', null, $this->prepareFileStructure(static::$fileStructure));

        $this->assertTrue($this->finder->locateRoot($root->url()));
        $this->assertSame('vfs://root', $this->finder->getDrupalRoot());
        $this->assertSame('vfs://root', $this->finder->getComposerRoot());
        $this->assertSame('vfs://root/vendor', $this->finder->getVendorDir());

        $this->assertTrue($this->finder->locateRoot($root->url() . '/misc'));
        $this->assertSame('vfs://root', $this->finder->getDrupalRoot());
        $this->assertSame('vfs://root', $this->finder->getComposerRoot());
        $this->assertSame('vfs://root/vendor', $this->finder->getVendorDir());

        $root = vfsStream::setup(
            'root',
            null,
            ['project' => $this->prepareFileStructure(static::$fileStructure)]
        );
        $this->assertFalse(
            $this->finder->locateRoot($root->url()),
            'Not in the scope of the project'
        );
        $this->assertFalse($this->finder->getDrupalRoot());
        $this->assertFalse($this->finder->getComposerRoot());
        $this->assertFalse($this->finder->getVendorDir());
    }

    public function testDrupalComposerStructure()
    {
        $fileStructure = $this->getDrupalComposerStructure();
        $this->assertComposerStructure($fileStructure);
    }

    public function testDrupalComposerStructureWithCustomRoot()
    {
        $fileStructure = [
            'src' => static::$fileStructure,
            'composer.json' => [
                'require' => [
                    'drupal/core' => '*',
                ],
                'extra' => [
                    'installer-paths' => [
                        'src/core' => [
                            'type:drupal-core',
                        ],
                    ],
                ],
            ],
            'vendor' => [],
        ];
        unset($fileStructure['src']['composer.json']);
        unset($fileStructure['src']['vendor']);

        $fileStructure = $this->prepareFileStructure($fileStructure);
        $root = vfsStream::setup('root', null, $fileStructure);
        $this->assertTrue($this->finder->locateRoot($root->url() . '/src'));
        $this->assertSame('vfs://root/src', $this->finder->getDrupalRoot());
        $this->assertSame('vfs://root', $this->finder->getComposerRoot());
        $this->assertSame('vfs://root/vendor', $this->finder->getVendorDir());

        $this->assertTrue($this->finder->locateRoot($root->url() . '/src/misc'));
        $this->assertSame('vfs://root/src', $this->finder->getDrupalRoot());
        $this->assertSame('vfs://root', $this->finder->getComposerRoot());
        $this->assertSame('vfs://root/vendor', $this->finder->getVendorDir());

        $this->assertTrue($this->finder->locateRoot($root->url()));
        $this->assertSame('vfs://root/src', $this->finder->getDrupalRoot());
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

    public function testDrupalComposerStructureWithoutRequire()
    {
        $fileStructure = [
            'web' => static::$fileStructure,
            'composer.json' => [
                'extra' => [
                    'installer-paths' => [
                        'web/core' => [
                            'drupal/core',
                        ],
                    ],
                ],
            ],
        ];
        unset($fileStructure['web']['composer.json']);
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

    public function testDrupalDefaultStructureWithRealFilesystem()
    {
        $root = $this->tempdir(sys_get_temp_dir());
        $this->dumpToFileSystem(static::$fileStructure, $root);

        $this->assertTrue($this->finder->locateRoot($root));
        $this->assertSame($root, $this->finder->getDrupalRoot());
        $this->assertSame($root, $this->finder->getComposerRoot());
        $this->assertSame($root . '/vendor', $this->finder->getVendorDir());

        // Test symlink implementation
        $symlink = $this->tempdir(sys_get_temp_dir());
        $this->symlink($root, $symlink . '/foo');

        $this->assertTrue($this->finder->locateRoot($symlink . '/foo'));
        $this->assertSame($root, $this->finder->getDrupalRoot());
        $this->assertSame($root, $this->finder->getComposerRoot());
        $this->assertSame($root . '/vendor', $this->finder->getVendorDir());
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
        $this->dumpToFileSystem(static::$fileStructure, $root);

        $module = $this->tempdir(sys_get_temp_dir());
        $module_link = $root . '/modules/foo';
        $this->symlink($module, $module_link);

        $this->assertTrue($this->finder->locateRoot($module_link));
        $this->assertSame($root, realpath($this->finder->getDrupalRoot()));
        $this->assertSame($root, realpath($this->finder->getComposerRoot()));
        $this->assertSame($root . '/vendor', realpath($this->finder->getVendorDir()));
    }

    public function testDrupalWithCustomVendor()
    {
        $root = $this->tempdir(sys_get_temp_dir());
        $fileStructure = static::$fileStructure;
        $fileStructure['composer.json'] = [
            'config' => [
                'vendor-dir' => 'vendor-foo'
            ]
        ];
        $fileStructure['vendor-foo'] = [];
        $this->dumpToFileSystem($fileStructure, $root);

        $this->assertTrue($this->finder->locateRoot($root));
        $this->assertSame($root, realpath($this->finder->getDrupalRoot()));
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
