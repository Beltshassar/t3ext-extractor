<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\Extractor\Em;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Mapping controller for Extension Manager.
 *
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class MappingController extends AbstractConfigurationField
{
    /**
     * @var string
     */
    protected $extensionKey = 'extractor';

    /**
     * @var array
     */
    protected $settings;

    /**
     * MappingController constructor.
     */
    public function __construct()
    {
        if (version_compare(TYPO3_version, '9.0', '<')) {
            $this->settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey] ?? '') ?? [];
        } else {
            $this->settings = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$this->extensionKey] ?? [];
        }
    }

    /**
     * Renders the mapping module.
     *
     * @param array $params Field information to be rendered
     * @param \TYPO3\CMS\Extensionmanager\ViewHelpers\Form\TypoScriptConstantsViewHelper $pObj
     * @return string
     */
    public function render(array $params, $pObj)
    {
        if (version_compare(TYPO3_version, '9.0', '<')) {
            $resourcesPath = '../' . ExtensionManagementUtility::siteRelPath($this->extensionKey) . 'Resources/Public/';
        } else {
            $resourcesPath = '../' . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($this->extensionKey)) . 'Resources/Public/';
        }

        if (version_compare(TYPO3_branch, '9.0', '>=')) {
            //$uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $ajaxUrlAnalyze = ''; //$uriBuilder->buildUriFromRoute('ajax_extractor_analyze');
            $ajaxUrlProcess = ''; //$uriBuilder->buildUriFromRoute('ajax_extractor_process');
        } else {
            $ajaxUrlAnalyze = BackendUtility::getAjaxUrl('extractor_analyze');
            $ajaxUrlProcess = BackendUtility::getAjaxUrl('extractor_process');
        }
        $inlineJs = 'var extractorAnalyzeAction = \'' . $ajaxUrlAnalyze . '\';';
        $inlineJs .= 'var extractorProcessAction = \'' . $ajaxUrlProcess . '\';';

        $pageRenderer = $this->getPageRenderer();
        $inlineJs .= PHP_EOL . 'require(["TYPO3/CMS/Extractor/configuration"]);';
        $pageRenderer->addJsFile($resourcesPath . 'JavaScript/extractor.js');
        $pageRenderer->addJsInlineCode($this->extensionKey, $inlineJs);

        $pageRenderer->addCssFile($resourcesPath . 'Css/select2.min.css');
        $pageRenderer->addCssFile($resourcesPath . 'Css/configuration.css');

        $html = [];
        $html[] = $this->smartFormat($this->translate('settings.mapping_configuration.description'));
        $html[] = '<div class="tx-extractor">';
        $html[] = '<div class="row">';
        $html[] = '<div class="col-md-3">';
        // Choose file
        $html[] = $this->getFileSelector();
        $html[] = '<div id="tx-extractor-preview"></div>';
        $html[] = '<div id="tx-extractor-files">';
        $html[] = '<p>' . $this->translate('settings.mapping_configuration.files', true) . '</p>';
        $html[] = '<ol></ol>';
        $html[] = '</div>';
        $html[] = '</div>'; // <div class="col-md-3">
        $html[] = '<div class="col-md-9">';
        // Service
        $html[] = $this->getServiceSelector();
        //  FAL Property
        $html[] = $this->getFalPropertySelector();
        // Metadata Property
        $html[] = '<div class="form-group">';
        $label = $this->translate('settings.mapping_configuration.property', true);
        $html[] = '<label for="tx-extractor-property">' . $label . '</label>';
        $html[] = '<input type="text" id="tx-extractor-property" readonly="readonly" class="form-control" />';
        $html[] = '</div>';
        // Processor
        $html[] = $this->getProcessorSelector();
        // Sample Value
        $html[] = '<div class="form-group">';
        $label = $this->translate('settings.mapping_configuration.sample', true);
        $html[] = '<label for="tx-extractor-sample">' . $label . '</label>';
        $html[] = '<input type="text" id="tx-extractor-sample" readonly="readonly" class="form-control" />';
        $html[] = '</div>';
        // Output
        $html[] = '<div class="form-group">';
        $label = $this->translate('settings.mapping_configuration.output', true);
        $html[] = '<label for="tx-extractor-output">' . $label . '</label>';
        $html[] = '<input type="text" id="tx-extractor-output" readonly="readonly" class="form-control" />';
        $html[] = '</div>';
        // JSON
        $html[] = '<div class="form-group">';
        $label = $this->translate('settings.mapping_configuration.json', true);
        $html[] = '<label for="tx-extractor-json">' . $label . '</label>';
        $html[] = '<textarea id="tx-extractor-json" rows="4" class="form-control"></textarea>';
        $html[] = '</div>';
        $label = $this->translate('settings.mapping_configuration.json.copy', true);
        $html[] = '<button id="tx-extractor-copy" class="btn btn-default">' . $label . '</button>';
        $html[] = '</div>'; // <div class="col-md-9">
        $html[] = '</div>'; // <div class="row">
        $html[] = '<div class="row" style="margin-top:2em">';
        $html[] = '<div class="col-md-12"><pre id="tx-extractor-metadata"></pre></div>';
        $html[] = '</div>'; // <div class="row">
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

    /**
     * Returns a file selector.
     *
     * @return string
     */
    protected function getFileSelector()
    {
        $samplePath = 'EXT:' . $this->extensionKey . '/Resources/Public/Samples/';
        $sampleFiles = GeneralUtility::getFilesInDir(GeneralUtility::getFileAbsFileName($samplePath));

        $files = [];
        foreach ($sampleFiles as $file) {
            $files['samples'][$samplePath . $file] = $file;
        }
        ksort($files['samples']);

        try {
            $folder = $this->getDefaultFolder();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        $userFiles = $folder->getFiles();
        foreach ($userFiles as $file) {
            if ($file->getName() === 'index.html') {
                continue;
            }
            $key = 'file:' . $file->getStorage()->getUid() . ':' . $file->getIdentifier();
            $files['custom'][$key] = $file->getName();
        }
        if (!empty($files['custom'])) {
            ksort($files['custom']);
        }

        $label = $this->translate('settings.mapping_configuration.chooseFile', true);
        $output = '<label for="tx-extractor-file">' . $label . '</label>';
        $output .= '<select id="tx-extractor-file" class="form-control"><option value=""></option>';

        foreach ($files as $category => $f) {
            if (!empty($f)) {
                $label = $this->translate('settings.mapping_configuration.chooseFile.' . $category, true);
                $output .= '<optgroup label="' . $label . '">';
                foreach ($f as $path => $name) {
                    $output .= '<option value="' . htmlspecialchars($path) . '">' . htmlspecialchars($name) . '</option>';
                }
                $output .= '</optgroup>';
            }
        }

        $output .= '</select>';

        return $output;
    }

    /**
     * Returns an extractor service selector.
     *
     * @return string
     */
    protected function getServiceSelector()
    {
        $services = [
            'enable_tika' => ['tika', 'Apache Tika'],
            'enable_php' => ['php', 'PHP'],
            'enable_tools_exiftool' => ['exiftool', 'exiftool'],
            'enable_tools_pdfinfo' => ['pdfinfo', 'pdfinfo'],

        ];

        $options = [];
        foreach ($services as $key => $valueTitle) {
            if (!empty($this->settings[$key])) {
                $options[$valueTitle[0]] = $valueTitle[1];
            }
        }

        $output = $this->getHtmlSelect(
            'tx-extractor-service',
            'settings.mapping_configuration.service',
            $options
        );

        return $output;
    }

    /**
     * Returns a FAL property selector.
     *
     * @return string
     */
    protected function getFalPropertySelector()
    {
        $options = [];
        $fields = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_metadata')
            ->getSchemaManager()
            ->listTableColumns('sys_file_metadata');
        foreach ($fields as $field => $_) {
            switch (true) {
                case GeneralUtility::isFirstPartOfStr($field, 't3ver_'):
                case GeneralUtility::isFirstPartOfStr($field, 't3_'):
                case GeneralUtility::isFirstPartOfStr($field, 'l10n_'):
                case GeneralUtility::isFirstPartOfStr($field, 'zzz_deleted_'):
                case in_array($field, ['uid', 'pid', 'tstamp', 'crdate', 'cruser_id', 'file', 'sys_language_uid', 'fe_groups']):
                    // Nothing to do
                    break;
                default:
                    $title = $field;
                    if (isset($GLOBALS['TCA']['sys_file_metadata']['columns'][$field]['label'])) {
                        $title .= ' [' . $this->translate($GLOBALS['TCA']['sys_file_metadata']['columns'][$field]['label']) . ']';
                    }
                    $options[$field] = $title;
                    break;
            }
        }

        $output = $this->getHtmlSelect(
            'tx-extractor-fal',
            'settings.mapping_configuration.fal',
            $options
        );

        return $output;
    }

    /**
     * Returns a processor selector.
     *
     * @return string
     */
    protected function getProcessorSelector()
    {
        $processors = $GLOBALS['TYPO3_CONF_VARS']['EXT'][$this->extensionKey]['processors'] ?? [];
        $options = array_combine($processors, $processors);

        $output = $this->getHtmlSelect(
            'tx-extractor-processor',
            'settings.mapping_configuration.processor',
            $options,
            true
        );

        return $output;
    }

    /**
     * Creates a HTML dropdown list.
     *
     * @param string $id
     * @param string $labelKey
     * @param array $options
     * @param bool $prependEmpty
     * @return string
     */
    protected function getHtmlSelect($id, $labelKey, array $options, $prependEmpty = false)
    {
        $output = '<div class="form-group">';
        $output .= '<label for="' . htmlspecialchars($id) . '">' . $this->translate($labelKey, true) . '</label>';
        $output .= '<select id="' . htmlspecialchars($id) . '" class="form-control">';

        if ($prependEmpty) {
            $output .= '<option value=""></option>';
        }
        foreach ($options as $key => $value) {
            $output .= sprintf('<option value="%s">%s</option>', htmlspecialchars($key), htmlspecialchars($value));
        }

        $output .= '</select>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Smartly formats a text.
     *
     * @param string $text
     * @return string
     */
    protected function smartFormat($text)
    {
        $lines = GeneralUtility::trimExplode(LF, $text);
        $output = '';
        foreach ($lines as $line) {
            if (!empty($output)) {
                $output .= ' ';
            }
            if (empty($line)) {
                $output .= '</p><p>';
            } else {
                $line = htmlspecialchars($line);
                if (strpos($line, '__USERDIR__') !== false) {
                    try {
                        $userDirectory = $this->getDefaultFolder();
                        $path = rtrim($userDirectory->getStorage()->getConfiguration()['basePath'], '/') . $userDirectory->getIdentifier();
                    } catch (\Exception $e) {
                        $path = 'fileadmin/user_upload/';
                    }


                    $line = str_replace('__USERDIR__', '<tt>' . htmlspecialchars($path) . '</tt>', $line);
                }
                $output .= $line;
            }
        }

        $output = '<p>' . $output . '</p>';
        return $output;
    }

    /**
     * Returns the default directory where user samples should be stored.
     *
     * @return \TYPO3\CMS\Core\Resource\Folder
     */
    protected function getDefaultFolder()
    {
        /** @var \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory */
        $resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);

        $defaultStorage = $resourceFactory->getDefaultStorage();

        if ($defaultStorage === null) {
            throw new \Exception('Ouch! Please edit storage "fileadmin/" and mark it as "default storage".', 1454413362);
        }

        $folder = $defaultStorage->getDefaultFolder();
        return $folder;
    }

    /**
     * Returns current PageRenderer.
     *
     * @return \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected function getPageRenderer()
    {
        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        return $pageRenderer;
    }
}
