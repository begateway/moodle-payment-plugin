<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3513cc87b2c7c6b96238f01a5d31792d
{
    public static $prefixLengthsPsr4 = array (
        'B' => 
        array (
            'BeGateway\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'BeGateway\\' => 
        array (
            0 => __DIR__ . '/..' . '/begateway/begateway-api-php/lib/BeGateway',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3513cc87b2c7c6b96238f01a5d31792d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3513cc87b2c7c6b96238f01a5d31792d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit3513cc87b2c7c6b96238f01a5d31792d::$classMap;

        }, null, ClassLoader::class);
    }
}