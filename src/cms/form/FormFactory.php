<?php
                               
namespace CMS\Forms;

use Nette;                     
use Nette\Application\UI\Form;
use Tracy\Debugger;

class FormFactory
{
    use Nette\SmartObject;
    
    private $_ratio = [1,5];

    /** @var \App\Universal\Translator */
    private $_translator;

    public function setRatio(array $ratio)
    {
        $this->_ratio = $ratio;
    }

    /** #var Nette\Database\Context */
    private $_database;

    public function __construct(\App\Model\Translator $tr, Nette\Database\Context $db)
    {
        $this->_translator = $tr;
        $this->_database = $db;
    }

    /**
     * @return Form
     */
    public function create($ratio = null, $groupName = null, $cols = null)
    {   
        $form = new Form;
        
        //if (null === $ratio) { $ratio = $this->_ratio; }
        $ratio = $ratio ?? $this->_ratio;
    
        $ratio_const = 12 / ( $ratio[0] + $ratio[1] );
        $ratio_l = $ratio[0] * $ratio_const;
        $ratio_r = $ratio[1] * $ratio_const;
        
        //$renderer = new \SmartCMS\BootstrapHorizontalFormRenderer;
        $renderer = new \AlesWita\FormRenderer\BootstrapV4Renderer;
        $renderer->wrappers['label']['.class'] = "col-sm-{$ratio_l} control-label";
        $renderer->wrappers['control']['container'] = "div class=\"col-sm-{$ratio_r} controls\"";
        $renderer->wrappers['buttons']['container'] = "div class=\"col-sm-offset-{$ratio_l} col-sm-{$ratio_r} form-actions\"";
        
        $form->setRenderer($renderer);
        $form->setTranslator($this->_translator);

        Debugger::barDump($groupName,  __METHOD__.'group name');
        Debugger::barDump($cols, 'cols');

        if (!is_null($cols) && !is_null($groupName)) {
            $this->generateForm($groupName, $cols, $form);
        }

        return $form;
    }

    protected function generateForm($name, $cols, &$form)
    {
        foreach ($cols as $col)
        {
            switch ($col->type)
            {
                case 'string':
                case 'double precision':
                    $form->addText($col->name, $col->title);
                    break;
                case 'integer':

                    if (is_null($col->reference)) {
                        $form->addText($col->name, $col->title);
                    } else {

                        //Debugger::barDump($this->_database, 'Databse');
                        //$values = $this->_database->table($col->reference['table'])->select("{$col->reference['col']}, title")->fetchAll();
                        $query = "SELECT a.{$col->reference['col']}, b.title FROM {$col->reference['table']} a JOIN {$col->reference['table']}_text b ON (a.id = b.{$col->reference['table']}_id)";
                        $values = $this->_database->query($query);
                        $selectVals = [];

                        foreach ($values as $value)
                        {
                            $selectVals[] = [$value['id'], $value['title']];
                        }

                        $form->addSelect($col->name, $col->title, $selectVals)->setTranslator(null);
                    }

                    break;

            }
        }
    }
}   

