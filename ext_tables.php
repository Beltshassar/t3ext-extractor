<?php
defined('TYPO3_MODE') || die();

$boot = function (string $_EXTKEY): void {
    if (TYPO3_MODE === 'BE') {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['sv']['extractor'] = [
            'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_reports.xlf:report_title',
            'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_reports.xlf:report_description',
            'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Images/tx_sv_report.png',
            'report' => \Causal\Extractor\Report\ServicesListReport::class
        ];
    }
};

$boot('extractor');
unset($boot);
