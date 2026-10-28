<?php

declare(strict_types=1);

namespace WonderWp\Component\Search\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WonderWp\Component\Search\Service\AbstractPostSearchService;
use WonderWp\Component\Search\Tests\Fixtures\TestablePostSearchService;

final class AbstractPostSearchServiceTest extends TestCase
{
    private TestablePostSearchService $service;

    protected function setUp(): void
    {
        $GLOBALS['wpdb'] = new \wpdb();
        $this->service = new TestablePostSearchService();
    }

    public function testSelectQueryIncludesFulltextMatchAndPostTypeFilter(): void
    {
        $sql = $this->service->exposeQuerySql('hello', ['post'], 'SELECT');

        $this->assertStringContainsString('MATCH (post_title,post_content,post_excerpt,post_name) AGAINST', $sql);
        $this->assertStringContainsString("*hello*", $sql);
        $this->assertStringContainsString(AbstractPostSearchService::SEARCH_MODIFIER, $sql);
        $this->assertStringContainsString("post_type IN ('post')", $sql);
        $this->assertStringContainsString('ORDER BY score DESC', $sql);
    }

    public function testGiveSetResultsAppendsLimitAndOffset(): void
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $this->service->exposeGiveSetResults('news', ['limit' => 5, 'offset' => 10]);

        $this->assertCount(1, $wpdb->executedQueries);
        $this->assertStringEndsWith('LIMIT 5 OFFSET 10', $wpdb->executedQueries[0]);
    }
}
