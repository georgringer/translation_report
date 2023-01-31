<?php
declare(strict_types=1);

namespace GeorgRinger\TranslationReport\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class TranslationRepository
{
    protected QueryBuilder $queryBuilder;
    private const TABLE = 'tx_translation_report_item';

    public function countAll(): int
    {
        $queryBuilder = $this->getQueryBuilder();
        return (int)$queryBuilder->count('*')->from(self::TABLE)->executeQuery()->fetchOne();
    }

    public function getMostDuplicated(int $max = 10): array
    {
        $result = [];
        $queryBuilder = $this->getQueryBuilder();
        $list = $queryBuilder
            ->select('translation_default')
            ->addSelectLiteral($queryBuilder->expr()->count('*', 'count'))
            ->from(self::TABLE)
            ->groupBy('translation_default')
            ->orderBy('count', 'DESC')
            ->setMaxResults($max)
            ->executeQuery()
            ->fetchAllAssociative();
        foreach ($list as $item) {
            $qb = $this->getQueryBuilder();
            $rows = $qb->select('*')
                ->from(self::TABLE)
                ->where(
                    $qb->expr()->eq('translation_default', $qb->createNamedParameter($item['translation_default']))
                )
                ->executeQuery()->fetchAllAssociative();
            $result[] = [
                'translation' => $item['translation_default'],
                'count' => $item['count'],
                'usages' => $qb->executeQuery()->fetchAllAssociative(),
            ];
        }

        return $result;
    }

    protected function getQueryBuilder()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
    }
}
