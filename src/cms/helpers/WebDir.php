<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 6.11.18
 * Time: 11:27
 */

namespace CMS\Helpers;


class WebDir
{
    private $wwwDir;

    public function __construct($wwwDir) {
        $this->wwwDir = $wwwDir;
    }

    public function getPath($fromBaseDir=''){
        return $this->wwwDir.DIRECTORY_SEPARATOR.$fromBaseDir;
    }
}