<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Eccube\ControllerProvider;

use Silex\Application;
use Silex\ControllerProviderInterface;

class InstallControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        /* @var $controllers \Silex\ControllerCollection */
        $controllers = $app['controllers_factory'];

        // Controllerのコンストラクタにappを渡すためServiceControllerServiceProviderで設定する
        $app['install.controller'] = $app->share(function() use ($app) {
            return new \Eccube\Controller\Install\InstallController($app);
        });

        // installer
        $controllers->match('', "install.controller:index")->bind('install');
        $controllers->match('/step1', 'install.controller:step1')->bind('install_step1');
        $controllers->match('/step2', 'install.controller:step2')->bind('install_step2');
        $controllers->match('/step3', 'install.controller:step3')->bind('install_step3');
        $controllers->match('/step4', 'install.controller:step4')->bind('install_step4');
        $controllers->match('/step5', 'install.controller:step5')->bind('install_step5');

        $controllers->match('/complete', 'install.controller:complete')->bind('install_complete');

        $controllers->match('/migration', 'install.controller:migration')->bind('migration');
        $controllers->match('/migration_plugin', 'install.controller:migration_plugin')->bind('migration_plugin');
        $controllers->match('/migration_end', 'install.controller:migration_end')->bind('migration_end');

        return $controllers;
    }
}
