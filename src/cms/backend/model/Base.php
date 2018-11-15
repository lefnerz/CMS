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

abstract class Base extends BaseColumns
{
    use SmartObject;

    /** @var Nette\Database\Context */
    protected $_database;

    public function __construct(Context $db)
    {
        $this->_database = $db;

        parent::__construct();
    }

    protected function insert($data)
    {
        return $this->_database->table(self::TABLE_NAME)->insert($data);
    }

    protected function get($id)
    {
        return $this->_database->table(self::TABLE_NAME)->get($id);
    }

    public function getAll()
    {
        return $this->_database->table($this::TABLE_NAME);
    }

    public function getBy($column, $value)
    {
        return $this->_database->table($this::TABLE_NAME)->where($column, $value);
    }
}

