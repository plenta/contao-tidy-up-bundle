<?php

declare(strict_types=1);

/**
 * Plenta Tidy Up Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2023, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\ContaoTidyUpBundle\Classes;

use Contao\BackendTemplate;
use Contao\Input;
use Contao\ModuleModel;
use Contao\System;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TidyUpModules
{
    public function run()
    {
        $request = System::getContainer()->get('request_stack')->getMasterRequest();
        if ('POST' === $request->getMethod()) {
            if (!empty($modules = $request->request->get('module'))) {
                foreach ($modules as $id => $value) {
                    if ($value) {
                        $module = ModuleModel::findByPk($id);
                        $module->delete();
                    }
                }
            }

            return new RedirectResponse($request->getUri());
        }

        $modules = ModuleModel::findByPid(Input::get('id'));
        $template = new BackendTemplate('be_tidy_up_modules');
        $template->modules = $modules;

        return $template->getResponse();
    }
}
