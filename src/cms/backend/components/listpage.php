<?php

use Nette\Application\UI;

class BackendListPageControl extends UI\Control
{
    public $heads = [];
    public $list = [];
    private $_tpl = 'detail';

    private $_translator;

    public function injectTranslator(\App\Model\Translator $tr)
    {
        $this->_translator = $tr;
    }

    public function setDetailTemplate($tpl)
    {
        $this->_tpl = $tpl;
    }

    protected function createTemplate($class = NULL)
    {
        $template = parent::createTemplate($class);
        $template->addFilter(NULL, 'Filters::common');
        return $template;
    }

    public function render($controls = null)
    {
        $template = $this->template;
        $template->list = $this->list;
        $template->heads = $this->heads;
        $template->controls = $controls;

        if (!is_null($this->template))
        {
            $template->tpl = $this->_tpl;
        }

        $template->setTranslator($this->_translator);
        $template->render(__DIR__ . '/listpage.latte');
    }

    public function renderScripts()
    {
        $template = $this->createTemplate();
        $template->render(__DIR__ . '/listpage-scripts.latte');
    }
}

