<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 28.11.18
 * Time: 15:44
 */

class MiniMenu  extends \Nette\Application\UI\Control
{
    public function render($controls = null)
    {
        $template = $this->createTemplate();
        $template->render(__DIR__ . '/minimenu.latte');
    }

    public function handleGeneral()
    {}
}