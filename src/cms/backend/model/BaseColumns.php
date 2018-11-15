<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 1.11.18
 * Time: 18:41
 */

namespace CMS\Model;


use Nette\SmartObject;
use Nette\Utils\Arrays;
use Tracy\Debugger;


abstract class BaseColumns
{
    const TABLE_NAME = '--BaseModel--';

    protected $cols = [];

    public function __construct()
    {
        $this->Load();
        $query = [];
        $query['install'] = $this->getInstallSQL();
        $query['update'] = $this->getUpdateSQL();
        //Debugger::barDump($query, $this->GetClassName($this) .' '. __METHOD__);
    }

    protected function Load()
    {
        $tableDefinition = [];

        foreach ($this->getTableDefinitionPaths() as $tableDefinitionPath) {

            if (!is_file($tableDefinitionPath)) continue;

            $jsonString = file_get_contents($tableDefinitionPath);
            if ("" == $jsonString) continue;

            $jsonArray = json_decode($jsonString, true);
            $tableDefinition = Arrays::mergeTree($tableDefinition, $jsonArray);
        }

        try {
            foreach ($tableDefinition as $tableName => $cols) {
                foreach ($cols as $colName => $colSpecification) {

                    $col = new TableColumn();
                    $col->tableName = $tableName;
                    $col->name = $colName;

                    try {
                        foreach ($colSpecification as $key => $spec) {

                            $col->$key = $spec;
                        }

                        $this->cols[$colName] = $col;
                    } catch (\Exception $e) {
                        var_dump($jsonString);
                        Debugger::barDump($e->getMessage(), $fileName);
                    }
                }
            }
        } catch (\Exception $exception)
        {
            var_dump($jsonString);
            Debugger::barDump($jsonString, $fileName);
            Debugger::barDump($exception->getMessage(), $fileName);
        }
    }

    public function getCols($groupName = null)
    {
        if (is_null($groupName))
        {
            return $this->cols;
        }

        $groupCols = [];

        foreach ($this->cols as $key => $col)
        {
            if ($groupName == $col->group) $groupCols[$key] = $col;
        }

        return $groupCols;
    }

    protected function getTableDefinitionPaths()
    {
        $dirList = [];
        $className = $this->GetClassName($this);

        // first dir is in Current directory
        $dirList[] = __DIR__."/".$className.".json";

        // second directory is in App Model directory
        $reflector = new \ReflectionClass(get_class($this));
        $dirList[] = dirname($reflector->getFileName()).'/'.$className.".json";;

        return $dirList;
    }

    public function getInstallSQL()
    {
        if (self::TABLE_NAME === $this::TABLE_NAME || empty($this->cols)) throw new \Exception("Database Table not specified");

        $tpl = 'CREATE TABLE "%1$s"  { '.PHP_EOL.' %2$s '.PHP_EOL.' } WITH ( OIDS=FALSE );'.PHP_EOL;

        $query = sprintf($tpl, $this::TABLE_NAME, $this->mkCols());
        $constraints = $this->mkConstraints();

        return ['create' => $query, 'constraints' => $constraints];
    }

    public function getUpdateSQL()
    {
        if (self::TABLE_NAME === $this::TABLE_NAME || empty($this->cols)) return null;

        $query = sprintf("SELECT * FROM information_schema.columns WHERE table_name = '%s';", $this::TABLE_NAME);
        $this->_database->query($query);

        foreach ($this->cols as $col) {


        }
    }

    protected function mkCols()
    {
        $strCols = [];

        foreach ($this->cols as $col) {
            $strCols[] = $col->GetCreationScript();
        }

        return implode(','.PHP_EOL.' ', $strCols);
    }

    protected function mkConstraints()
    {
        $constraints = [];
        $primaries = [];
        $references = [];

        foreach ($this->cols as $col) {

            if (true === $col->primary)
            {
                $primaries[] = $col->name;
            }

            $reference = $col->GetReferences();

            if (!empty($reference)) {
                $references[] = $reference;
            }

            if ($col->unique)
            {
                $references[] = $col->GetUnique();
            }
        }

        if (0 < count($primaries))
        {
            $primaryTemplate = 'ALTER TABLE "%1$s" ADD CONSTRAINT "%2$s" PRIMARY KEY ("%3$s");';
            $primaryName = sprintf("pkey_%s_%s", $this::TABLE_NAME, implode('_', $primaries));
            $constraints[] = sprintf($primaryTemplate, $this::TABLE_NAME, $primaryName, implode(',', $primaries));
        }

        $constraints = array_merge($constraints, $references);

        return implode(PHP_EOL, $constraints);
    }

    function GetClassName(object $classname)
    {
        $class = get_class($classname);
        if ($pos = strrpos($class, '\\')) return substr($class, $pos + 1);
        return $pos;
    }
}

class TableColumn
{
    use SmartObject;

    const COL_TYPE_ID = 'serial';
    const COL_TYPE_INTEGER = 'integer';
    const COL_TYPE_STRING = 'string';
    const COL_TYPE_TEXT = 'text';

    public $tableName;

    public $name;
    public $title;
    public $type;
    public $null = true;
    public $description;
    public $primary = false;
    public $unique = false;
    public $length;
    public $default;
    public $reference;
    public $group;
    public $list = false;

    public function GetCreationScript()
    {
        return
            "\"{$this->name}\" " .
            (self::COL_TYPE_STRING == $this->type ? 'character varying' : $this->type) .
            (self::COL_TYPE_STRING == $this->type ? " ({$this->length})" : '') .
            (!is_null($this->default) ? " DEFAULT {$this->default}" : '');
            //(true === $this->primary ? " PRIMARY KEY" : '');
    }

    public function GetReferences()
    {
        $constraint = "";

        if (!is_null($this->reference))
        {
            $tpl = 'ALTER TABLE "%1$s" ADD CONSTRAINT "fkey_%1$s_%3$2_%2$2_%4$s" FOREIGN KEY ("%2$s") REFERENCES "%3$s"("%4$s")'.
                (isset($this->reference['delete'])?" ON DELETE ".$this->reference['delete']:'').
                ';';

            $constraint = sprintf($tpl, $this->tableName, $this->name, $this->reference['table'], $this->reference['col']);
        }

        return $constraint;
    }

    public function GetUnique()
    {
        $unique = "";

        $tpl = 'ALTER TABLE "%1$s" ADD CONSTRAINT "unique_%1$s_%2$s" UNIQUE ("%2$s");';

        if ($this->unique) $unique = sprintf($tpl, $this->tableName, $this->name);

        return $unique;
    }

    public function GetUpdateScript()
    {
        return "ALTER TABLE {$this->tableName} ALTER COLUMN {$this->name} {$this->type}" . (self::COL_TYPE_STRING == $this->type ? " ({$this->length})" : '');
    }

    public function SetNullable($nullable = true)
    {
        $this->null = $nullable;
    }
}