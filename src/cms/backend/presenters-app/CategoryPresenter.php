<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 26.11.18
 * Time: 17:53
 */

namespace App\BackendModule\Presenters;


final class CategoryPresenter extends \CMS\Backend\Presenters\CategoryPresenter
{
    public function injectCategory(\App\Model\Category $category) { $this->model = $category; }
}