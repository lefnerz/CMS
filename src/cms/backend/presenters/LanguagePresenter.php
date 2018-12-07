<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 11/21/18
 * Time: 2:05 PM
 */

namespace CMS\Backend\Presenters;


abstract class LanguagePresenter extends BasePresenter
{
    public function injectModel(\App\Model\Language $model) { $this->model = $model; }
}