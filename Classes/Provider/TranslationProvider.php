<?php
declare(strict_types=1);

namespace GeorgRinger\TranslationReport\Provider;

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TranslationProvider
{

    protected Connection $connection;
    private const TABLE = 'tx_translation_report_item';

    public function __construct()
    {
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
    }

    public function fillDatabase()
    {
        $this->connection->truncate(self::TABLE);
        $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->create('default');
        $packages = $this->getExtensionLanguagePackDetails();

        $inserts = [];
        foreach ($packages as $package) {
            foreach ($package['files'] as $file) {
                $translations = $languageService->getLabelsFromResource($file);
                foreach ($translations as $key => $translation) {
                    $inserts[] = [
                        'package' => $package['key'],
                        'path' => str_replace('EXT:' . $package['key'] . '/', '', $file),
                        'translation_key' => $key,
                        'translation_default' => $translation,
                    ];
                }
            }
        }

        $chunks = array_chunk($inserts, 200);
        foreach ($chunks as $chunk) {
            $this->connection->bulkInsert(self::TABLE, $chunk, array_keys($chunk[0]));
        }
    }

    private function getExtensionLanguagePackDetails(): array
    {
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $activePackages = $packageManager->getActivePackages();
        $extensions = $data = [];
        foreach ($activePackages as $package) {
            $path = $package->getPackagePath();
            $finder = new Finder();
            try {
                $files = $finder->files()->in($path . 'Resources/Private/Language/')->name('*.xlf');
                if ($files->count() === 0) {
                    // This extension has no .xlf files
                    continue;
                }
                $allFiles = [];
                foreach ($files as $file) {
                    $finalPath = str_replace($path, 'EXT:' . $package->getPackageKey() . '/', $file->getPath()) . '/' . $file->getFilename();
                    if (str_contains($finalPath, 'Resources/Private/Language/Iso/')) {
                        continue;
                    }
                    $allFiles[] = $finalPath;
                }
                $data[] = [
                    'key' => $package->getPackageKey(),
                    'type' => $package->getPackageMetaData()->getPackageType(),
                    'files' => $allFiles,
                ];
            } catch (\InvalidArgumentException $e) {
                // Dir does not exist
                continue;
            }
        }
        return $data;
    }
}
