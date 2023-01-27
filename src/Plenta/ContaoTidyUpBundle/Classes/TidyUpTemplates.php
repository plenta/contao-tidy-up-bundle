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
use Contao\System;
use Contao\TemplateLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TidyUpTemplates
{
    public function run()
    {
        $template = new BackendTemplate('be_tidy_up_templates');
        $filesystem = System::getContainer()->get('filesystem');
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        $templates = [];
        $stdTemplates = TemplateLoader::getFiles();

        if ($filesystem->exists($projectDir.'/templates')) {
            $finder = new Finder();
            foreach ($finder->files()->in($projectDir.'/templates') as $file) {
                if (!\array_key_exists($templateName = $file->getBasename('.html5'), $stdTemplates)) {
                    $templates[$templateName] = $file;
                }
            }
        }

        $request = System::getContainer()->get('request_stack')->getMasterRequest();
        if ('POST' === $request->getMethod()) {
            if (!empty($templates = $request->request->get('template')) && !empty($templatePaths = $request->request->get('template_path'))) {
                foreach ($templates as $name => $value) {
                    if ($value) {
                        if (!empty($path = $templatePaths[$name])) {
                            $filesystem->remove($projectDir.'/templates/'.$path);
                        }
                    }
                }
            }

            return new RedirectResponse($request->getUri());
        }

        $template->templates = $templates;

        return $template->getResponse();
    }
}
