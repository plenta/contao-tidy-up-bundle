<?php

declare(strict_types=1);

/**
 * Plenta Tidy Up Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2023, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

$GLOBALS['BE_MOD']['design']['themes']['tidyUpModules'] = [
    \Plenta\ContaoTidyUpBundle\Classes\TidyUpModules::class,
    'run',
];

$GLOBALS['BE_MOD']['design']['tpl_editor']['tidyUpTemplates'] = [
    \Plenta\ContaoTidyUpBundle\Classes\TidyUpTemplates::class,
    'run',
];
