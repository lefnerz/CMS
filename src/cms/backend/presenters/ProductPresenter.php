<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 11/21/18
 * Time: 2:54 PM
 */

namespace CMS\Backend\Presenters;


use App\Model\Product;
use Tracy\Debugger;

abstract class ProductPresenter extends BasePresenter
{
    public function injectModel(Product $product) { $this->model = $product; }

    protected function getSubMenu()
    {
        return [
            'general' => [
                'title' => 'menu.general',
                'model' => 'Product:detail',
                'keys' => [ $this->currentId, 'general' ]
            ],
            'parametter' => [
                'title' => 'menu.parametter',
                'model' => 'Product:detail',
                'keys' => [ $this->currentId, 'parametter' ]
            ],
            'photos' => [
                'title' => 'menu.photos',
                'model' => 'Product:detail',
                'keys' => [ $this->currentId, 'photos' ]
            ],
            'meta' => [
                'title' => 'menu.meta',
                'model' => 'this',
                'keys' => [ $this->currentId, 'meta' ]
            ]
        ];
    }
}