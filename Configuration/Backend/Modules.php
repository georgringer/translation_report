<?php

return [
    'translation_report' => [
        'parent' => 'system',
        'access' => 'admin',
        'path' => '/module/system/translation',
        'iconIdentifier' => 'translation-report-module',
        'labels' => 'LLL:EXT:translation_report/Resources/Private/Language/locallang.xlf',
        'routes' => [
            '_default' => [
                'target' => \GeorgRinger\TranslationReport\Controller\TranslationReportController::class . '::handleRequest',
            ],
        ],
    ],
];
