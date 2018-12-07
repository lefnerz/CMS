<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 25.10.18
 * Time: 16:26
 */

namespace CMS\Model;

use Nette\Database\Context;
use Nette\SmartObject;
use Tracy\Debugger;
use CMS\Helpers;

abstract class Base extends DatabaseUpdater
{
    use SmartObject;

    public function __construct(Context $db, Helpers\WebDir $webDir)
    {
        parent::__construct($db, $webDir);
    }

    protected function insert($data)
    {
        return $this->_database->table($this->tableName())->insert($data);
    }

    public function Get($id)
    {
        return $this->_database->table($this->tableName())->get($id);
    }

    public function GetColumns($cols)
    {
        $select = implode(",", array_keys($cols));

        Debugger::barDump($cols, "columns");

        return $this->_database->table($this->tableName())->select($select)->fetchAll();
    }

    public function GetAll()
    {
        return $this->_database->table($this->tableName());
    }

    public function GetWhere($column, $value)
    {
        return $this->_database->table($this->getTableName())->where($column, $value);
    }

    protected function tableName()
    {
        return $this->getTableName($this);
    }

    protected function getTableName(object $object)
    {
        return strtolower($this->getClassName($object));
    }

    protected function getClassName(object $object)
    {
        $class = get_class($object);
        if ($pos = strrpos($class, '\\')) return substr($class, $pos + 1);
        return $pos;
    }
}

