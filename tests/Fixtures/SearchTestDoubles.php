<?php

declare(strict_types=1);

namespace WonderWp\Component\Search\Tests\Fixtures;

use WonderWp\Component\Search\Service\AbstractPostSearchService;

final class TestablePostSearchService extends AbstractPostSearchService
{
    protected function giveSetLabel()
    {
        return 'Posts';
    }

    public function exposeQuerySql(string $searchText, array $postTypes = [], string $action = 'SELECT'): string
    {
        return $this->getQuerySql($searchText, $postTypes, $action);
    }

    public function exposeGiveSetResults(string $query, array $opts = []): array
    {
        return $this->giveSetResults($query, $opts);
    }
}
