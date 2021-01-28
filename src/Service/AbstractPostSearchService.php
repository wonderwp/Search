<?php

namespace WonderWp\Component\Search\Service;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Media\Medias;
use WonderWp\Component\PluginSkeleton\AbstractManager;
use WonderWp\Component\Search\Result\SearchResultInterface;

abstract class AbstractPostSearchService extends AbstractSetSearchService
{
    const POST_TYPE       = 'post';
    const SEARCH_MODIFIER = 'IN BOOLEAN MODE';

    /**
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * @inheritDoc
     */
    public function __construct(AbstractManager $manager = null)
    {
        parent::__construct($manager);

        global $wpdb;
        $this->wpdb = $wpdb;
    }

    protected function giveSetName()
    {
        return static::POST_TYPE . '-set';
    }

    /** @inheritdoc */
    protected function giveSetTotalCount($query, array $opts = [])
    {

        $queryStr = $this->getQuerySql($query, [static::POST_TYPE], 'COUNT');
        $res      = $this->wpdb->get_results($queryStr);

        return !empty($res) && !empty($res[0]) && !empty($res[0]->cpt) ? $res[0]->cpt : 0;
    }

    /** @inheritdoc */
    protected function giveSetResults($query, array $opts = [])
    {

        $resCollection = [];
        $queryStr      = $this->getQuerySql($query, [static::POST_TYPE], 'SELECT');
        $queryStr      .= ' LIMIT ' . $opts['limit'] . ' OFFSET ' . $opts['offset'];
        $dbCollection  = $this->wpdb->get_results($queryStr);
        if (!empty($dbCollection)) {
            foreach ($dbCollection as $dbRow) {
                $resCollection[] = $this->mapToRes($dbRow);
            }
        }

        return $resCollection;
    }

    /**
     * Build sql query looking for a text in posts, for a given type and action.
     *
     * @param string $searchText
     * @param array  $postTypes
     * @param string $action
     *
     * @return string
     */
    protected function getQuerySql($searchText, $postTypes = [], $action = "SELECT")
    {
        $queryBuilder = $this->getQueryBuilder($searchText, $postTypes, $action);

        return $this->computeQueryFromBuilder($queryBuilder);
    }

    protected function getQueryBuilder($searchText, $postTypes = [], $action = "SELECT")
    {
        global $wpdb;
        $query = [
            'select'  => [],
            'from'    => [],
            'where'   => [1],
            'orderby' => [],
        ];

        if ($searchText) {
            // Search for longer sentences
            $searchText = '*' . trim($searchText, '*') . '*';

            if ($action == 'COUNT') {
                $query['select'][] = "COUNT(*) as cpt";
            } else {
                $query['select'][] = "$wpdb->posts.*";
                $query['select'][] = "MATCH (" . implode(',', $this->getIndexedFields()) . ") AGAINST ('" . $searchText . "' " . self::SEARCH_MODIFIER . ") as score";
            }
            $query['from'][] = "$wpdb->posts";

            $searchablePostStatus = ['publish', 'private'];
            if (in_array('attachment', $postTypes)) {
                $searchablePostStatus[] = 'inherit';
            }
            $searchablePostStatus = apply_filters('wwp-search.post_search.searchable_post_status', $searchablePostStatus, $postTypes, $action);
            if (!empty($searchablePostStatus)) {
                $query['where'][] = "AND $wpdb->posts.post_status IN ('" . implode("','", $searchablePostStatus) . "')";
            }

            if (!empty($postTypes)) {
                $query['where'][] = "AND $wpdb->posts.post_type IN ('" . implode(',', $postTypes) . "')
            ";
            }

            $query['where'][] = "AND MATCH (" . implode(',', $this->getIndexedFields()) . ") AGAINST ('" . $searchText . "' " . self::SEARCH_MODIFIER . ")";

            if ($action == 'SELECT') {
                $query['orderby'][] = "score DESC";
            }

        }

        return apply_filters('AbstractPostSearchService.query', $query, $searchText, $postTypes, $action);
    }

    protected function computeQueryFromBuilder(array $query)
    {
        $queryStr =
            'SELECT ' . implode(',', $query['select']) .
            ' FROM ' . implode(' ', $query['from']);
        if (!empty($query['where'])) {
            $queryStr .= ' WHERE ' . implode(' ', $query['where']);
        }
        if (!empty($query['orderby'])) {
            $queryStr .= ' ORDER BY ' . implode(',', $query['orderby']);
        }

        return $queryStr;
    }

    protected function getIndexedFields()
    {
        return [
            'post_title',
            'post_content',
            'post_excerpt',
            'post_name',
        ];
    }

    /**
     * Turn a post into a search result.
     *
     * @param \WP_Post $post
     *
     * @return SearchResultInterface
     */
    protected function mapToRes($post)
    {
        /** @var SearchResultInterface $res */
        $res = Container::getInstance()->offsetGet('wwp.search.result');

        $res->setTitle($post->post_title);
        $res->setThumb(Medias::getFeaturedImage($post));
        $res->setContent($post->post_excerpt . '' . apply_filters('the_content', $post->post_content));
        $post->filter = 'sample';
        $res->setLink(get_permalink($post));

        return $res;
    }
}
