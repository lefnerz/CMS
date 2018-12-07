<?php

namespace App;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter()
	{
        $router = new RouteList;
        $router[] = new Route('storage/[<action>/]<img_x>/<img_y>[/<image>]', [
            'presenter' => 'Storage',
            'module' => 'Backend',
            'action' => 'img',
        ]);
        $router[] = new Route('admin/[<lang [a-z]{2}>/]<presenter>/<action>[/<id>][/<id2>]', [
            'lang' => 'cs',
            'module' => 'Backend',
            'presenter' => 'Dashboard',
            'action' => 'default',
            'id2' => 'general'
        ]);
        $router[] = new Route('[<lang [a-z]{2}>/]<presenter>/<action>[/<id>]', [
            'lang' => 'cs',
            'module' => 'Frontend',
            'presenter' => 'Homepage',
            'action' => 'default'
        ]);
        return $router;
	}
}
