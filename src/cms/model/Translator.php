<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 25.10.18
 * Time: 16:47
 */

namespace CMS\Model;

use CMS\BasePresenter;
use CMS\Helpers\WebDir;
use Nette;
use Tracy\Debugger;

class Translator extends Base implements Nette\Localization\ITranslator
{
    use Nette\SmartObject;

    public $language = 1;

    /** @var \App\Model\Language */
    private $_language;

    private $_cache = [];

    const TABLE_NAME = 'translator';
    const COL_LANG = 'language_id';
    const COL_TERM = 'term';
    const COL_TER2 = 'translate';

    function __construct(\Nette\Database\Context $db, \CMS\Helpers\WebDir $webDir/*,  \App\Model\Language $lng*/)
    {
        parent::__construct($db, $webDir);
        //$this->_language = $lng;
    }

    public function translate($message, $count = null)
    {
        //return $message;

        if ($this->_hasTranslate($message)) {
            return $this->_translate($message);
        } else {
            $this->_loadTranslates();

            if ($this->_hasTranslate($message)) {
                return $this->_translate($message);
            } else {
                $this->_addTerm($message);
            }
        }

        return $message;
    }

    public function getAll()
    {
        return $this->_database->table(self::TABLE_NAME)->where(self::COL_LANG, $this->language)->fetchAll();
    }

    public function setTermTranslate($term, $lang, $translate)
    {
        return $this->_database->table(self::TABLE_NAME)->where([self::COL_TERM => $term, self::COL_LANG => $lang])->update([self::COL_TER2 => $translate]);
    }

    public function setLang($lang)
    {
        $this->language = $lang;
    }

    private function _hasTranslate($msg)
    {
        $has = array_key_exists($msg, $this->_cache);
        return $has;
    }

    private function _loadTranslates()
    {
        $this->_cache = [];
        $translates = $this->getAll();

        foreach ($translates as $translate) {
            $this->_cache[$translate->{self::COL_TERM}] = $translate->{self::COL_TER2};
        }
    }

    private function _addTerm($term)
    {
        $this->_database->table(self::TABLE_NAME)->insert([
            self::COL_TERM => $term,
            self::COL_LANG => $this->language,
            //self::COL_TER2 => $term,
        ]);
    }

    private function _translate($msg)
    {
        $term = $this->_cache[$msg];

        return empty($term) ? $msg : $term;
    }
}
