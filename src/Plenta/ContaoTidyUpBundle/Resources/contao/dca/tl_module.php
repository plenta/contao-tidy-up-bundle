<?php

declare(strict_types=1);

/**
 * Plenta Tidy Up Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2023, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

$GLOBALS['TL_DCA']['tl_module']['list']['global_operations']['tidy_up'] = [
    'href' => 'key=tidyUpModules',
    'icon' => 'wrench.gif',
    'label' => &$GLOBALS['TL_LANG']['MSC']['contao_tidy_up']['tidy_up'],
];
