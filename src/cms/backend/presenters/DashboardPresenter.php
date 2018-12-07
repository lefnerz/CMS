<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 11/15/18
 * Time: 11:14 AM
 */

namespace CMS\Backend\Presenters;


use Nette\Utils\Arrays;
use Tracy\Debugger;

class DashboardPresenter extends BasePresenter
{
    protected $modules = [
        'App\Model\Language',
        'App\Model\Translator',
        'App\Model\Category',
        'App\Model\Product',
        'App\Model\Vat',
    ];

    //public function injectModel(\App\Model\Language $model) { $this->model = $model; }

    public function actionUpdate()
    {
        $queries = [];

        foreach ($this->modules as $model) {

            $obj = $this->context->getByType($model);

            try
            {
                $install = $obj->GetUpdateSQL();

                $queries = array_merge($queries, $install);

            } catch (\Exception $e) {
                Debugger::barDump($e->getMessage(), "error ".$model);
                continue;
            }
        }

        Debugger::barDump($queries, 'queries for db update');
        $this->template->updateResult = implode(PHP_EOL, Arrays::flatten($queries));

    }

    public function actionInstal()
    {
        $queries = [];

        foreach ($this->modules as $model) {

            $obj = $this->context->getByType($model);

            try
            {
                $install = $obj->GetInstallSQL();

                foreach ($install as $k => $query) {

                    if (!isset($queries[$k]))
                    {
                        $queries[$k] = [];
                    }

                    $queries[$k] = array_merge($queries[$k], Arrays::flatten($query));
                }

            } catch (\Exception $e) {
                Debugger::barDump($e->getMessage(), "error ".$model);
                continue;
            }
        }

        Debugger::barDump($queries, 'queries for db update');
        $this->template->updateResult = implode(PHP_EOL, Arrays::flatten([$queries['create'], $queries['references'], $queries['init']]));
    }
}