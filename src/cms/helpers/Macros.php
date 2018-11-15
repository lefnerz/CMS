<?php
namespace CMS\Helpers;

use Latte;
use Latte\MacroNode;
use Latte\PhpWriter;
use Tracy\Debugger;

class Macros extends Latte\Macros\MacroSet
{
    public static function install(\Latte\Compiler $compiler)
    {
        $set = new static($compiler);
        $set->addMacro('script', [$set, 'macroScript']);
        $set->addMacro('script', [$set, 'macroJs']);
        $set->addMacro('css', [$set, 'macroCss']);
        $set->addMacro('customerDiff', [$set, 'macroCustomerDiff']);
    }

    public function macroCustomerDiff(MacroNode $node, PhpWriter $writer)
    {
        $params = explode(' ', $node->args);

        return 'if (' . $params[0] . ' != ' . $params[1] . ') { echo "<br /><font title=\"Customer\'s update\" style=\"cursor: help;color: #f00\">{' . $params[1] . '}</font>"; }';
    }

    public function macroJs(MacroNode $node, PhpWriter $writer)
    {
        return $this->macroScript($node, $writer);
    }

    public function macroScript(MacroNode $node, PhpWriter $writer)
    {
        $param = explode(' ', $node->args);
        $str = '';

        foreach ($param as $file) {
            $filename = $this->_rootDir() . '/www' . $file;

            if (file_exists($filename)) {
                $str .= "<script type='text/javascript' src='{$file}?" . '".filectime("' . $filename . '")."' . "'></script>";
            } elseif (strpos($file, 'http') === 0) {
                $str .= "<script type='text/javascript' src='{$file}'></script>";
            } else {
                $str .= "<script type='text/javascript'>console.log('missing file: {$file} {$filename}')</script>";
            }

            $str .= "\n";
        }

        return "echo \"$str\"";
    }

    public function macroCss(MacroNode $node, PhpWriter $writter)
    {
        $param = explode(' ', $node->args);
        $str = '';

        foreach ($param as $file) {
            $filename = $this->_rootDir() . '/www' . $file;

            if (file_exists($filename)) {
                $str .= "<link rel='stylesheet' href='{$file}?" . '".filectime("' . $filename . '")."' . "' />";
            } elseif (strpos($file, 'http') === 0) {
                $str .= "<link rel='stylesheet' href='{$file}' />";
            } else {
                $str .= "<script type='text/javascript'>console.log('missing file: {$file} {$filename}')</script>";
            }

            $str .= "\n";
        }

        return "echo \"$str\"";

    }

    private $_rootDir;

    private function _rootDir()
    {
        if (is_null($this->_rootDir)) {
            $parts = explode(DIRECTORY_SEPARATOR, __DIR__);
            // cut last 6 parts because of: vendor/lefnerz/cms/src/cms/helpers
            $rootPathArray = array_slice($parts, 0, count($parts) - 6);
            $this->_rootDir = implode(DIRECTORY_SEPARATOR, $rootPathArray);
        }

        return $this->_rootDir;
    }
}

