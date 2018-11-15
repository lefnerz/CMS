<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 11/15/18
 * Time: 11:12 AM
 */

namespace CMS\Backend\Presenters;


use Nette\Application\UI\Presenter;

class BasePresenter extends Presenter
{
    /** @var \CMS\Helpers\WebDir */
    private $_webDir;

    public function injectWebDir(\CMS\Helpers\WebDir $webDir)
    {
        $this->_webDir = $webDir;
    }

    public function formatTemplateFiles()
    {
        $s = DIRECTORY_SEPARATOR;
        list(, $presenter) = \Nette\Application\Helpers::splitName($this->getName());

        $templates = [];
        $templates[] = $this->_webDir->getPath("app{$s}presenters{$s}backend{$s}templates{$s}{$presenter}{$s}{$this->view}.latte");
        $templates[] = $this->_webDir->getPath("vendor{$s}lefnerz{$s}cms{$s}src{$s}cms{$s}backend{$s}templates{$s}{$presenter}{$s}{$this->view}.latte");

        return $templates;
    }

    public function formatLayoutTemplateFiles()
    {
        $s = DIRECTORY_SEPARATOR;

        $layout = [];
        $layout[] = $this->_webDir->getPath("app{$s}presenters{$s}backend{$s}templates{$s}@layout.latte");
        $layout[] = $this->_webDir->getPath("vendor{$s}lefnerz{$s}cms{$s}src{$s}cms{$s}backend{$s}templates{$s}@layout.latte");

        return $layout;
    }
}