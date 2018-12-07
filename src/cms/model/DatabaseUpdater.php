<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 11/21/18
 * Time: 11:18 AM
 */

namespace CMS\Model;

use Nette;
use Nette\SmartObject;
use Tracy\Debugger;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;

class DatabaseUpdater
{
    const SPECIAL_RECORD_PREFIX = '!';
    const SPECIAL_RECORD_VALUES = 'values';

    /** @var Table */
    protected $tables;

    /** @var \Nette\Database\Context */
    protected $_database;

    /** @var \CMS\Helpers\WebDir */
    protected $_webDir;

    /**
     * DatabaseUpdater constructor.
     * @param Nette\Database\Context $_database
     * @param \CMS\Helpers\WebDir $_webDir
     */
    public function __construct(Nette\Database\Context $_database, \CMS\Helpers\WebDir $_webDir)
    {
        $this->_database = $_database;
        $this->_webDir = $_webDir;
    }

    public function getListHeaders()
    {
        if (is_null($this->tables)) {
            $this->LoadFiles();
        }

        $headers = [];

        foreach ($this->tables as $table) {
            $headers += $table->GetListHeaders();
        }

        return $headers;
    }

    public function getListValues($headers, $lang_id)
    {
        if (is_null($this->tables)) {
            $this->LoadFiles();
        }

        $tableAliases = ['ta', 'tb', 'tc', 'td', 'te', 'tf', 'tg', 'th', 'ti', 'tj', 'tk'];
        $tableInfos = [];

        $queryBegin = '';
        $queryParts = [];
        $cols = [];
        $tx = 0;
        $mainTable = $this->tableName();

        foreach ($headers as $header)
        {
            if (!isset($tableInfos[$header->table]))
            {
                $tableInfos[$header->table] = [
                    'alias' => $tableAliases[$tx++],
                    'cols' => [],
                ];
            }

            $tableInfos[$header->table]['cols'][] = "{$tableInfos[$header->table]['alias']}.{$header->name}";
        }

        foreach ($tableInfos as $table => $info)
        {
            if ($mainTable === $table)
            {
                $queryBegin = "SELECT %s FROM {$table} AS {$info['alias']}";
                $onJoin = "{$info['alias']}.id = %s AND %s = {$lang_id}";
                $cols[] = implode(',', ['id' => 'ta.id'] +$info['cols']);
            } else {
                $primaries = $this->tables[$table]->GetPrimaries();
                $queryPart = "LEFT JOIN {$table} AS {$info['alias']} ON ({$onJoin})";
                $pkey['lang'] = 'language_id';
                $pkey['main'] = 'language_id' == $primaries[1] ? $primaries[0] : $primaries[1];
                $queryParts[] = sprintf($queryPart, "{$info['alias']}.".$pkey['main'], "{$info['alias']}.language_id");
                $cols[] = implode(',', $info['cols']);
            }
        }

        $query = sprintf($queryBegin, implode(', ', $cols)).' '.implode(' ', $queryParts);

        return $this->_database->query($query);
    }

    public function LoadFiles()
    {
        if (!is_null($this->tables)) {
            return null; // already loaded
        }

        $tableDefinition = [];
        $files = $this->formatJsonFiles();
        //Debugger::barDump($files);

        foreach ($files as $file) {
            if (!file_exists($file)) continue;

            $jsonString = file_get_contents($file);
            if ("" == $jsonString) continue;

            $jsonArray = json_decode($jsonString, true);
            $tableDefinition = Arrays::mergeTree($tableDefinition, $jsonArray);
        }

        //Debugger::barDump($tableDefinition, 'tableDefinition');

        foreach ($tableDefinition as $tableName => $columns) {
            if (!isset($this->tables[$tableName])) {
                $table = new Table($tableName);
                $table->injectDatabase($this->_database);
                $this->tables[$tableName] = $table;
            }

            foreach ($columns as $columnName => $column) {
                if (!$this->isSpeacialData($columnName)) {
                    $this->tables[$tableName]->AddCol(new TableColumn($this->_database, $columnName, $column, $tableName));
                } else {
                    switch (Strings::after($columnName, self::SPECIAL_RECORD_PREFIX)) {
                        case self::SPECIAL_RECORD_VALUES:
                            $this->tables[$tableName]->AddSpecial(self::SPECIAL_RECORD_VALUES, $column);
                            break;
                    }
                }
            }
        }

        if (!is_null($this->tables)) {
            //Debugger::barDump($this->tables, 'tables '.implode(', ', $this->GetTableNames()));
        }
    }

    public function GetGroupCols($groupName)
    {
        if (is_null($this->tables)) {
            $this->LoadFiles();
        }
        /* take data only from primary table
        if (isset($this->tables[$this->tableName()]))
        {
            return $this->tables[$this->tableName()]->GetGroupCols($groupName);
        }
        */
        $groups = [];

        foreach ($this->tables as $table)
        {
            $groups[] = $table->GetGroupCols($groupName);
        }

        //Debugger::barDump(Arrays::flatten($groups), 'groups_flatten');

        return Arrays::flatten($groups);
    }

    public function GetDefaultsForGroup($groupName, $id, $language)
    {
        $cols = $this->GetGroupCols($groupName);
        $defaults = [];

        foreach ($cols as $col)
        {
            $table = $col->table;

            if (!isset($$table)) {
                $primary = $this->_database->table($table)->getPrimary();

                if (!is_array($primary)) {
                    $$table = $this->_database->table($table)->wherePrimary($id)->fetch();
                } else {

                    $keys = [];

                    foreach ($primary as $key)
                    {
                        switch ($key)
                        {
                            case 'language_id': $keys['language_id'] = $language; break;
                            default: $keys[$key] = $id;
                        }
                    }

                    $$table = $this->_database->table($table)->wherePrimary($keys)->fetch();
                }
            }

            if (false !== $$table) {
                $defaults[$col->name] = $$table->{$col->name};
            }
        }

        return $defaults;
    }

    public function GetInstallSQL()
    {
        if (is_null($this->tables)) {
            $this->LoadFiles();
        }

        $updateQueries = [];

        foreach ($this->tables as $table) {
            $updateQueries = array_merge($updateQueries, $table->GetInstallSQL());
        }

        return $updateQueries;
    }

    public function GetUpdateSQL()
    {
        if (is_null($this->tables)) {
            $this->LoadFiles();
        }

        $updateQueries = [];

        foreach ($this->tables as $table) {
            $updateQueries[] = $table->GetUpdateSQL();
        }

        return Arrays::flatten($updateQueries);
    }

    protected function GetTableNames()
    {
        $names = [];

        foreach ($this->tables as $table) {
            $names[] = $table->name;
        }

        return $names;
    }

    protected function isSpeacialData($columnName)
    {
        return Strings::startsWith($columnName, $this::SPECIAL_RECORD_PREFIX);
    }

    protected function formatJsonFiles()
    {
        $dirList = [];
        $className = $this->getClassName($this);
        $reflector = new \ReflectionClass(get_class($this));

        // first dir is in model Current directory
        //$dirList[] = dirname($reflector->getFileName()).'/../model/'.$className.".json";;
        $dirList[] = $this->_webDir->getPath("/vendor/lefnerz/cms/src/cms/model/{$className}.json");

        // second directory is in App Model directory
        $dirList[] = dirname($reflector->getFileName()) . '/' . $className . ".json";;

        return $dirList;
    }
}

class Table
{
    /** @var string */
    public $name;

    /** @var array TableColumn */
    protected $cols = [];

    /** @var \Nette\Database\Context */
    protected $_database;

    /** @var array */
    protected $special = [];

    public function injectDatabase(\Nette\Database\Context $db)
    {
        $this->_database = $db;
    }

    public function __construct(string $tableName)
    {
        $this->name = $tableName;
    }

    public function GetInstallSQL()
    {
        $createQueries = [];
        $colReferences = [];
        $colSpecials = [];

        if (0 < count($this->cols)) {
            $tpl = "CREATE TABLE {$this->name} ( %s ) WITH ( OIDS=FALSE );";
            $colCreate = [];

            foreach ($this->cols as $col) {
                $colCreate[] = $col->GetInstallSql();

                if (true == $col->reference) {
                    $colReferences[] = $col->GetReference($this->name);
                }
            }

            if (!is_null($this->special)) {
                $colSpecials[] = $this->GetSpecials();
            }

            $primaryKey = $this->_mkPrimaryKeys();
            if (!is_null($primaryKey)) {
                $colCreate[] = $primaryKey;
            }

            $createQueries[] = sprintf($tpl, implode(',', Arrays::flatten($colCreate)));
        }

        return ['create' => $createQueries, 'references' => $colReferences, 'init' => $colSpecials];
    }

    public function GetListHeaders()
    {
        $headers = [];

        foreach ($this->cols as $name => $col) {
            if (true == $col->list) {
                $headers[$name] = $col;
            }
        }

        return $headers;
    }

    public function GetPrimaries()
    {
        return $this->_database->table($this->name)->getPrimary();
    }

    public function GetSpecials()
    {
        $queries = [];

        foreach ($this->special as $name => $special) {
            switch ($name) {
                case 'values':
                    $queries[] = $this->_mkInitializeRecords($special);
                    break;
            }
        }

        return $queries;
    }

    public function GetUpdateSQL()
    {
        $updateQueries = [];
        $dbDefinitionQuery = sprintf("SELECT * FROM information_schema.columns WHERE table_name = '%s';", $this->name);
        $dbDefinition = $this->_database->query($dbDefinitionQuery)->fetchAll();
        $dbColumns = [];

        if (0 < count($dbDefinition)) {
            foreach ($dbDefinition as $dbRow) {

                if (isset($this->cols[$dbRow->column_name])) {
                    //Debugger::barDump($dbRow, 'dbRow '.$dbRow->column_name);
                    $columnData = [];
                    $columnData['name'] = $dbRow->column_name;
                    $columnData['type'] = 'character varying' == $dbRow->data_type ? TableColumn::COL_TYPE_STRING : $dbRow->data_type;
                    $columnData['length'] = $dbRow->character_maximum_length;
                    $columnData['is_nullable'] = 'YES' == $dbRow->is_nullable;
                    $columnData['default'] = $dbRow->column_default;

                    //Debugger::barDump($columnData, 'columnData');

                    $dbColumn = new TableColumn($this->_database, $dbRow->column_name, $columnData);
                    $dbColumns[$dbColumn->name] = $dbColumn;
                    //Debugger::barDump($dbColumn, 'dbColumn');
                    //$updateQueries[] = $this->cols[$dbColumn->name]->GetUpdateSQL($this->name, $dbColumn);
                }
            }

            foreach ($this->cols as $col) {

                if (isset($dbColumns[$col->name])) {
                    $updateQueries[] = $col->GetUpdateSql($this->name, $dbColumns[$col->name]);
                } else {
                    $updateQueries[] = $col->GetInstallSql($this->name);
                }
            }
        } else {
            $updateQueries[] = $this->GetInstallSQL();
        }

        return $updateQueries;
    }

    public function AddCol(TableColumn $col)
    {
        $this->cols[$col->name] = $col;
    }

    public function AddSpecial(string $name, array $data)
    {
        $this->special[$name] = $data;
    }

    public function GetCol(string $name)
    {
        if (!isset($this->cols[$name])) {
            return null;
        }

        return $this->cols[$name];
    }

    public function GetGroupCols($groupName)
    {
        $cols = [];

        foreach ($this->cols as $name => $col) {
            if ($groupName == $col->group)
            {
                $cols[$name] = $col;
            }
        }

        return $cols;
    }

    private function _mkPrimaryKeys()
    {
        $tpl = "PRIMARY KEY (%s)";
        $primaries = [];
        $serials = [];

        foreach ($this->cols as $col) {
            if ($col->primary) {
                $primaries[] = $col->name;
            }

            if (TableColumn::COL_TYPE_ID == $col->type) {
                $serials[] = $col->name;
            }
        }

        if (!empty($primaries) || !empty($serials)) {
            return sprintf($tpl, implode(',', 0 < count($primaries) ? $primaries : $serials));
        }

        return null;
    }

    private function _mkInitializeRecords(array $records)
    {
        $tpl = 'INSERT INTO %1$s (%2$s) VALUES (%3$s);';
        $queries = [];

        foreach ($records as $record) {

            $vals = [];
            foreach (array_values($record) as $value) {
                if (is_string($value)) {
                    $vals[] = "'{$value}'";
                } else if (is_bool($value)) {
                    $vals[] = true == (bool)$value ? "'T'" : "'F'";
                } else $vals[] = $vals;
            }

            $queries[] = sprintf($tpl, $this->name, implode(',', array_keys($record)), implode(',', $vals));
        }

        return $queries;
    }
}

class TableColumn
{
    use SmartObject;

    const COL_TYPE_ID = 'serial';
    const COL_TYPE_STRING = 'string';
    const COL_TYPE_INTEGER = 'integer';
    const COL_TYPE_TEXT = 'text';

    public $table;
    public $name;
    public $title;
    public $type;
    public $length;
    public $primary;
    public $is_nullable = true;
    public $default;
    public $reference = false;
    public $list = false;
    public $group = '';
    public $listValue;

    private $_database;

    public function __construct(Nette\Database\Context $db, string $name, array $data, $table = null)
    {
        $this->_database = $db;
        $this->name = $name;

        if (!is_null($table))
        {
            $this->table = $table;
        }

        $this->init($data);
    }

    /*
     * @sring tableName = if null, return shorter query version for CREATE DATABASE
     */
    public function GetInstallSql(string $tableName = null)
    {
        $tpl = "";
        $queries = [];

        // --- --- --- --- --- create table

        if (is_null($tableName)) {
            $tpl = "%s %s";
        } else {
            $tpl = "ALTER TABLE {$tableName} ADD COLUMN %s %s;";
        }

        $queries[] = sprintf($tpl, $this->name, $this->_mkQueryType());

        return $queries;
    }

    public function GetReference($tableName)
    {
        $referenceTpl = 'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s);';
        $queries = [];

        if ($this->reference) {

            list($referenceTable, $referenceColumn) = explode('_', $this->name);
            $constraintName = "{$tableName}_{$this->name}";

            $queries[] = sprintf($referenceTpl, $tableName, $constraintName, $this->name, $referenceTable, $referenceColumn);
        }

        return $queries;
    }

    public function GetReferenceValueList($lang_id)
    {
        $values = [];
        $query = $this->getReferenceValueQuery($lang_id);
        //Debugger::barDump($query, 'REFERENCE VALUES QUERY');

        try {
            foreach ($this->_database->query($query)->fetchAll() as $row) {
                $values[$row->id] = $row->title;
            }
        } catch (\Exception $exception) {
            Debugger::barDump(['col' => $this->name, 'table' => $this->table, 'language_id' => $lang_id], "Can't get values from reference.");
        }

        return $values;
    }

    protected function getReferenceValueQuery($lang_id)
    {
        list($referenceTable, $referenceColumn) = explode('_', $this->name);

        $tpl = "SELECT t.id id, r.title title FROM {$referenceTable} t LEFT JOIN {$referenceTable}_language r ON (r.{$this->name} = t.id AND r.language_id = {$lang_id})";

        return $tpl;
    }

    public function GetUpdateSQL(string $tableName, TableColumn $dbData)
    {
        // this - new definition from file
        // dbData - current definition in table

        if ($this->name != $dbData->name) throw new \Exception("Can't update column by column with different name.");
        $tpl = "ALTER TABLE {$tableName} %s COLUMN %s %s;";
        $updateQuery = [];

        //Debugger::barDump([$this->type, $dbData->type], 'type '.$this->name);

        if ($this->type !== $dbData->type && self::COL_TYPE_ID != $this->type) {
            $updateQuery[] = sprintf($tpl, "ALTER", $this->name, "TYPE " . $this->_mkQueryType($dbData));
        }

        if ($this->is_nullable != $dbData->is_nullable && self::COL_TYPE_ID != $this->type) {
            $updateQuery[] = sprintf($tpl, "ALTER", $this->name, (true == $this->is_nullable ? "DROP " : "SET ") . "NOT NULL");
        }

        if ($this->default != $dbData->default && self::COL_TYPE_ID != $this->type) {
            $updateQuery[] = sprintf($tpl, "ALTER", $this->name, (null == $this->default ? "DROP DEFAULT" : "SET DEFAULT '{$this->default}'"));
        }

        //Debugger::barDump($updateQuery, 'updateQuery');

        return $updateQuery;
    }

    private function _mkQueryType(TableColumn $dbData = null)
    {
        $query = "";

        if (is_null($dbData)) {
            $query = "{$this->GetType()}" . (false == $this->is_nullable ? ' NOT NULL' : '') . (!empty($this->default) ? " SET DEFAULT \"{$this->default}\"" : "");
        } else {
            if ($this->type != $dbData->type) {
                $query = "{$this->GetType()}";
            }
        }

        return $query;
    }

    public function GetType()
    {
        $type = $this->type;

        switch ($this->type) {
            case self::COL_TYPE_STRING:
                $type = "character varying({$this->length})";
                break;
        }

        return $type;
    }

    protected function init(array $data)
    {
        foreach ($data as $key => $val) {
            $this->{$key} = $val;
        }
    }
}