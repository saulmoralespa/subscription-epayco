<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite8e1d6316681d8670d9de888acd9a1b8
{
    public static $files = array (
        '941748b3c8cae4466c827dfb5ca9602a' => __DIR__ . '/..' . '/rmccue/requests/library/Deprecated.php',
    );

    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WpOrg\\Requests\\' => 15,
        ),
        'E' => 
        array (
            'Epayco\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WpOrg\\Requests\\' => 
        array (
            0 => __DIR__ . '/..' . '/rmccue/requests/src',
        ),
        'Epayco\\' => 
        array (
            0 => __DIR__ . '/..' . '/epayco/epayco-php/src',
        ),
    );

    public static $classMap = array (
        'Requests' => __DIR__ . '/..' . '/rmccue/requests/library/Requests.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInite8e1d6316681d8670d9de888acd9a1b8::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInite8e1d6316681d8670d9de888acd9a1b8::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInite8e1d6316681d8670d9de888acd9a1b8::$classMap;

        }, null, ClassLoader::class);
    }
}
