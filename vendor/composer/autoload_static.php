<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit07f017b829a55d7b4fe7fdd05d90b2a8
{
    public static $fallbackDirsPsr4 = array (
        0 => __DIR__ . '/../..' . '/app',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->fallbackDirsPsr4 = ComposerStaticInit07f017b829a55d7b4fe7fdd05d90b2a8::$fallbackDirsPsr4;
            $loader->classMap = ComposerStaticInit07f017b829a55d7b4fe7fdd05d90b2a8::$classMap;

        }, null, ClassLoader::class);
    }
}