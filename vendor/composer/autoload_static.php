<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc08014b32f79549ee15fc0b574740b1f
{
    public static $prefixLengthsPsr4 = array (
        'V' => 
        array (
            'Valitron\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Valitron\\' => 
        array (
            0 => __DIR__ . '/..' . '/vlucas/valitron/src/Valitron',
        ),
    );

    public static $prefixesPsr0 = array (
        'J' => 
        array (
            'JasonGrimes' => 
            array (
                0 => __DIR__ . '/..' . '/jasongrimes/paginator/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'JasonGrimes\\Paginator' => __DIR__ . '/..' . '/jasongrimes/paginator/src/JasonGrimes/Paginator.php',
        'Valitron\\Validator' => __DIR__ . '/..' . '/vlucas/valitron/src/Valitron/Validator.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc08014b32f79549ee15fc0b574740b1f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc08014b32f79549ee15fc0b574740b1f::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitc08014b32f79549ee15fc0b574740b1f::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitc08014b32f79549ee15fc0b574740b1f::$classMap;

        }, null, ClassLoader::class);
    }
}
