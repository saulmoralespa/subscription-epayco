<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit13dd8d576abaeef29d08bbb6a70da4f7
{
    public static $prefixLengthsPsr4 = array (
        'E' => 
        array (
            'Epayco\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Epayco\\' => 
        array (
            0 => __DIR__ . '/..' . '/epayco/epayco-php/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'R' => 
        array (
            'Requests' => 
            array (
                0 => __DIR__ . '/..' . '/rmccue/requests/library',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit13dd8d576abaeef29d08bbb6a70da4f7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit13dd8d576abaeef29d08bbb6a70da4f7::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit13dd8d576abaeef29d08bbb6a70da4f7::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}