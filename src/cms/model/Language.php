<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 11/16/18
 * Time: 12:46 PM
 */

namespace CMS\Model;


abstract class Language extends Base
{
    const TABLE_NAME = "language";

    public function GetLangIdByCode($code)
    {
        return $this->_database->table($this->tableName())->where('code', $code)->fetch()->id;
    }
}