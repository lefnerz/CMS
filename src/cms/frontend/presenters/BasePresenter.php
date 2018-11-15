<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 11/15/18
 * Time: 9:51 AM
 */

namespace CMS\Frontend\Presenters;

use CMS\Helpers\WebDir;
use Nette;
use Tracy\Debugger;

class BasePresenter extends Nette\Application\UI\Presenter
{
    /** @var WebDir */
    private $_webDir;

    public function injectWebDir(WebDir $webDir)
    {
        $this->_webDir = $webDir;
    }

    public function formatTemplateFiles()
    {
        $s = DIRECTORY_SEPARATOR;
        list(, $presenter) = \Nette\Application\Helpers::splitName($this->getName());

        $templates = [];
        $templates[] = $this->_webDir->getPath("app{$s}presenters{$s}frontend{$s}templates{$s}{$presenter}{$s}{$this->view}.latte");
        $templates[] = $this->_webDir->getPath("vendor{$s}lefnerz{$s}cms{$s}src{$s}cms{$s}frontend{$s}templates{$s}{$presenter}{$s}{$this->view}.latte");

        return $templates;
    }

    public function formatLayoutTemplateFiles()
    {
        $s = DIRECTORY_SEPARATOR;

        $layout = [];
        $layout[] = $this->_webDir->getPath("app{$s}presenters{$s}frontend{$s}templates{$s}@layout.latte");
        $layout[] = $this->_webDir->getPath("vendor{$s}lefnerz{$s}cms{$s}src{$s}cms{$s}frontend{$s}templates{$s}@layout.latte");

        return $layout;
    }
}
