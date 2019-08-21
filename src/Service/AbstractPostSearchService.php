<?php

namespace WonderWp\Component\Search\Service;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Media\Medias;
use WonderWp\Component\PluginSkeleton\AbstractManager;
use WonderWp\Component\Search\Result\SearchResultInterface;

abstract class AbstractPostSearchService extends AbstractSetSearchService
{
    const POST_TYPE = 'post';
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
        $res = $this->wpdb->get_results($queryStr);

        return !empty($res) && !empty($res[0]) && !empty($res[0]->cpt) ? $res[0]->cpt : 0;
    }

    /** @inheritdoc */
    protected function giveSetResults($query, array $opts = [])
    {

        $resCollection = [];
        $queryStr = $this->getQuerySql($query, [static::POST_TYPE], 'SELECT');
        $queryStr .= ' LIMIT ' . $opts['limit'] . ' OFFSET ' . $opts['offset'];
        $dbCollection = $this->wpdb->get_results($queryStr);
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
     * @param array $postTypes
     * @param string $action
     *
     * @return string
     */
    protected function getQuerySql($searchText, $postTypes = [], $action = "SELECT")
    {
        global $wpdb;
        $queryStr = NULL;

        if ($searchText) {
            // Search for longer sentences
            $searchText = '*' . trim($searchText, '*') . '*';

            if ($action == 'COUNT') {
                $queryStr = "SELECT COUNT(*) as cpt";
            } else {
                $queryStr = "SELECT $wpdb->posts.*, MATCH (" . implode(',', $this->getIndexedFields()) . ") AGAINST ('" . $searchText . "' " . self::SEARCH_MODIFIER . ") as score";
            }
            $queryStr .= "
                    FROM $wpdb->posts";
            $queryStr .= "
                    WHERE 1 ";

            $searchablePostStatus = ['publish', 'private'];
            if (in_array('attachment', $postTypes)) {
                $searchablePostStatus[] = 'inherit';
            }
            $searchablePostStatus = apply_filters('wwp-search.post_search.searchable_post_status', $searchablePostStatus, $postTypes, $action);
            if (!empty($searchablePostStatus)) {
                $queryStr .= " AND $wpdb->posts.post_status IN ('" . implode("','", $searchablePostStatus) . "')";
            }

            if (!empty($postTypes)) {
                $queryStr .= "
                    AND $wpdb->posts.post_type IN ('" . implode(',', $postTypes) . "')
            ";
            }

            $queryStr .= "
        AND MATCH (" . implode(',', $this->getIndexedFields()) . ") AGAINST ('" . $searchText . "' " . self::SEARCH_MODIFIER . ")";

            if ($action == 'SELECT') {
                $queryStr .= "
            ORDER BY score DESC";
            }
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
