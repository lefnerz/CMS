<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 28.11.18
 * Time: 15:15
 */

class EmptyComponent extends \Nette\Application\UI\Control
{
    public function render($controls = null)
    {
        $template = $this->createTemplate();
        $template->render(__DIR__ . '/emptycomponent.latte');
    }

    public function renderScripts()
    {
        $template = $this->createTemplate();
        $template->render(__DIR__ . '/emptycomponent.latte');
    }
}