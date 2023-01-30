<?php

declare(strict_types=1);

/**
 * Plenta Tidy Up Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2023, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\ContaoTidyUpBundle\Controller;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/contao/_tidyUp", defaults={"_scope": "backend"})
 */
class AjaxController extends AbstractController
{
    protected Connection $database;
    protected ContaoFrameworkInterface $contaoFramework;

    protected string $projectDir;

    protected Filesystem $filesystem;

    public function __construct(Connection $database, ContaoFrameworkInterface $contaoFramework, string $projectDir, Filesystem $filesystem)
    {
        $this->database = $database;
        $this->contaoFramework = $contaoFramework;
        $this->projectDir = $projectDir;
        $this->filesystem = $filesystem;
    }

    /**
     * @Route("/module/createLookUp", methods={"GET"})
     */
    public function generateTableLookUpModule()
    {
        $this->contaoFramework->initialize();

        $schemaManager = $this->database->getSchemaManager();
        $tables = $schemaManager->listTables();
        foreach ($tables as $table) {
            Controller::loadDataContainer($table->getName());
        }
        $arr = [];

        foreach ($GLOBALS['TL_DCA'] as $table => $dc) {
            $arr[$table] = ['text' => [], 'id' => [], 'multiple' => []];
            if (($dc['config']['ptable'] ?? null) === 'tl_module') {
                $arr[$table]['id'][] = 'pid';
            }
            foreach ($dc['fields'] as $fieldName => $field) {
                if (empty($field['sql'])) {
                    continue;
                }
                if ('text' === $field['inputType'] || 'textarea' === $field['inputType']) {
                    if (empty($field['eval']['rgxp'])) {
                        $arr[$table]['text'][] = $fieldName;
                        continue;
                    }
                }
                if ('moduleWizard' === $field['inputType']) {
                    $arr[$table]['multiple'][] = $fieldName;
                    continue;
                }
                if ((!empty($field['foreignKey']) && false !== stripos($field['foreignKey'], 'tl_module')) || false !== stripos($fieldName, 'module')) {
                    if (empty($field['eval']['multiple'])) {
                        $arr[$table]['id'][] = $fieldName;
                    } else {
                        $arr[$table]['multiple'][] = $fieldName;
                    }
                }
            }
        }

        return new JsonResponse(['tableLookUp' => $arr]);
    }

    /**
     * @Route("/module/analyze", methods={"POST"})
     */
    public function analyzeModule(Request $request)
    {
        $this->contaoFramework->initialize();
        $id = $request->request->get('id');
        $lookUp = json_decode($request->request->get('lookUp'), true);
        foreach ($lookUp as $table => $arr) {
            if (!empty($arr['id'])) {
                $result = $this->database->prepare('SELECT count(id) AS count FROM '.$table.' WHERE '.implode(' = :id OR ', $arr['id']).' = :id')->executeQuery(['id' => $id])->fetchAssociative();
                if ($result['count'] > 0) {
                    return new JsonResponse([true]);
                }
            }

            if (!empty($arr['multiple'])) {
                $result = $this->database->executeQuery('SELECT count(id) AS count FROM '.$table.' WHERE '.implode(" LIKE '%\"".$id."\"%' OR ", $arr['multiple'])." LIKE '%\"".$id."\"%'")->fetchAssociative();
                if ($result['count'] > 0) {
                    return new JsonResponse([true]);
                }
            }

            if (!empty($arr['text'])) {
                $result = $this->database->executeQuery('SELECT count(id) AS count FROM '.$table.' WHERE '.implode(" LIKE '%{{insert_module::".$id."}}%' OR ", $arr['text'])." LIKE '%{{insert_module::".$id."}}%'")->fetchAssociative();
                if ($result['count'] > 0) {
                    return new JsonResponse([true]);
                }
            }
        }

        if ($this->filesystem->exists($this->projectDir.'/templates')) {
            $finder = new Finder();
            foreach ($finder->files()->in($this->projectDir.'/templates/*') as $file) {
                if (false !== strpos($file->getContents(), '{{insert_module::'.$id.'}}')) {
                    return new JsonResponse([true]);
                }
            }
        }

        return new JsonResponse([false]);
    }

    /**
     * @Route("/template/createLookUp", methods={"GET"})
     */
    public function generateTableLookUpTemplate()
    {
        $this->contaoFramework->initialize();

        $schemaManager = $this->database->getSchemaManager();
        $tables = $schemaManager->listTables();
        foreach ($tables as $table) {
            Controller::loadDataContainer($table->getName());
        }
        $arr = [];

        foreach ($GLOBALS['TL_DCA'] as $table => $dc) {
            $arr[$table] = ['name' => [], 'text' => []];
            foreach ($dc['fields'] as $fieldName => $field) {
                if (empty($field['sql'])) {
                    continue;
                }
                if ('text' === $field['inputType'] || 'textarea' === $field['inputType']) {
                    if (empty($field['eval']['rgxp'])) {
                        $arr[$table]['text'][] = $fieldName;
                        continue;
                    }
                }
                if (false !== stripos($fieldName, 'tpl') || false !== stripos($fieldName, 'template')) {
                    $arr[$table]['name'][] = $fieldName;
                }
            }
        }

        return new JsonResponse(['tableLookUp' => $arr]);
    }

    /**
     * @Route("/template/analyze", methods={"POST"})
     */
    public function analyzeTemplate(Request $request)
    {
        $this->contaoFramework->initialize();
        $template = $request->request->get('template');
        $lookUp = json_decode($request->request->get('lookUp'), true);
        foreach ($lookUp as $table => $arr) {
            if (!empty($arr['name'])) {
                $result = $this->database->prepare('SELECT count(id) AS count FROM '.$table.' WHERE '.implode(' = :template OR ', $arr['name']).' = :template')->executeQuery(['template' => $template])->fetchAssociative();
                if ($result['count'] > 0) {
                    return new JsonResponse([true]);
                }
            }
            if (!empty($arr['text'])) {
                $result = $this->database->executeQuery('SELECT count(id) AS count FROM '.$table.' WHERE '.implode(" LIKE '%{{file::".$template."%}}%' OR ", $arr['text'])." LIKE '%{{file::".$template."%}}%'")->fetchAssociative();
                if ($result['count'] > 0) {
                    return new JsonResponse([true]);
                }
            }
        }

        return new JsonResponse([false]);
    }
}
