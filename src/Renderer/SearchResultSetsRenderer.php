<?php

namespace WonderWp\Component\Search\Renderer;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Search\ResultSet\SearchResultSetInterface;
use WonderWp\Theme\Core\Component\PaginationComponent;
use WonderWp\Component\Search\Result\SearchResultInterface;

class SearchResultSetsRenderer implements SearchResultsRendererInterface
{
    /**
     * @var SearchResultSetInterface[]
     */
    protected $sets = [];

    /**
     * @return SearchResultSetInterface[]
     */
    public function getSets()
    {
        return $this->sets;
    }

    /**
     * @param SearchResultSetInterface[] $sets
     *
     * @return static
     */
    public function setSets($sets)
    {
        $this->sets = $sets;

        return $this;
    }

    /** @inheritdoc */
    public function getMarkup(array $results, $query, array $opts = [])
    {
        $this->setSets($results);

        $markup = '';
        if (!empty($this->sets)) {
            foreach ($this->sets as $set) {
                $markup .= $this->getSetMarkup($set, $query, $opts);
            }
        } else {
            $markup = $this->getNoResultMarkup($opts);
        }

        return $markup;
    }

    protected function buildBaseQuery($query, array $opts = []){
        return [
            's' => urlencode($query),
            't' => $opts['search_service'],
            'v' => 'list',
        ];
    }

    /**
     * @param SearchResultSetInterface $set
     * @param string                   $query
     * @param array                    $opts
     *
     * @return string
     */
    public function getSetMarkup(SearchResultSetInterface $set, $query, array $opts = [])
    {
        $totalCount = $set->getTotalCount();

        if ($totalCount <= 0) {
            return '';
        }

        $markup  = '';
        $results = $set->getCollection();
        if (!empty($results)) {
            $isListView = isset($opts['view']) && $opts['view'] === 'list';

            $baseQueryComponents = $this->buildBaseQuery($query, $opts);

            if ($isListView) {
                $markup .= $this->getBackButtonMarkup($baseQueryComponents['s']);
            }

            $markup .= $this->getHeaderSingleResultMarkup($set, $totalCount, $opts);

            foreach ($results as $res) {
                $markup .= "<li>" . $this->getSingleResultMarkup($res, $query) . "</li>";
            }

            $markup .= $this->getFooterSingleResultMarkup();

            if ($isListView) {
                $container           = Container::getInstance();
                /** @var PaginationComponent $paginationComponent */
                $paginationComponent = $container['wwp.theme.component.pagination'];
                $markup              .= $paginationComponent->getMarkup([
                    'nbObjects'     => $totalCount,
                    'perPage'       => $opts['limit'],
                    'paginationUrl' => '/?' . http_build_query($baseQueryComponents + ['pageno' => '{pageno}']),
                    'currentPage'   => $opts['page'],
                ]);
                $markup .= $this->getBackButtonMarkup($baseQueryComponents['s']);
            } else {
                if (!isset($opts['limit']) || (isset($opts['limit']) && $totalCount > $opts['limit'])) {
                    $markup .= '<a href="/?' . http_build_query($baseQueryComponents) . '" class="search-all-res-in-cat">' . __('see.all.results', WWP_THEME_TEXTDOMAIN) . '</a>';
                }
            }

            $markup .=
                '</div>';

        }

        return $markup;
    }

    public function getHeaderSingleResultMarkup(SearchResultSetInterface $set, $totalCount, array $opts = []){
        $markup = '';
        $class = '';
        if(isset($opts['class'])){
            $class = $opts['class'];
        }

        $markup .=
            '<div class="search-result-set search-result-set-' . (!empty($opts['view']) ? $opts['view'] : 'extrait') . ' search-result-set-' . sanitize_title($set->getName()) . '">
                <div class="seat-head"> ' .
            '<span class="set-total">' . (int)$totalCount . '</span> ' .
            '<span class="set-title">' . $set->getLabel() . '</span>
                </div>
                <ul class="set-results ' . $class . '">';

        return $markup;
    }

    public function getBackButtonMarkup(string $search){
        return '<a href="/?s=' . $search . '" class="search-go-back">' . __('back.to.results', WWP_THEME_TEXTDOMAIN) . '</a>';;
    }

    public function getFooterSingleResultMarkup(){
        return '</ul>';
    }

    public function getSingleResultMarkup(SearchResultInterface $res, $query){
        $markup = '';
        if (method_exists($res, 'getLink') && !empty($res->getLink())) {
            $markup .= '<a href="' . $res->getLink() . '">';
        }

        if(method_exists($res, 'getTitle')){
            $markup .= '<span class="res-title">' . $this->highlightSearchTerm($res->getTitle(), $query) . '</span>';
        }

        if (method_exists($res, 'getContent') && !empty($res->getContent())) {
            $markup .= '<div class="res-content">' . $this->getMeaningFulContent($res->getContent(), $query) . '</div>';
        }

        if (method_exists($res, 'getLink') && !empty($res->getLink())) {
            $markup .= '</a>';
        }

        return $markup;
    }

    /**
     * @param array $opts
     *
     * @return string
     */
    public function getNoResultMarkup(array $opts = [])
    {
        return apply_filters('wwp.search.noresult', 'No result');
    }

    /**
     * @param $content
     * @param $query
     * @return mixed|string
     */
    protected function getMeaningFulContent($content, $query)
    {
        $text = str_replace(["\r\n", "\r"], " ", strip_tags($content));
        $text_without_accent = $this->removeAccents($text);
        $query_without_accent = $this->removeAccents($query);
        $testpos = !empty($query) ? mb_stripos($text_without_accent, $query_without_accent) : 0;

        $size    = 140;
        $half    = ceil($size / 2);
        $mindif  = $testpos - $half;
        $maxdif  = $testpos + $half;

        if ($mindif < 0) {
            $minbound = 0;
            $pre_     = '';
        } else {
            $minbound = $mindif;
            $pre_     = '...';
        }
        if ($maxdif > $size) {
            $maxbound = $size;
            $sr_      = '...';
        } else {
            $maxbound = $maxdif;
            $sr_      = '...';
        }

        $text = $pre_ . substr($text, $minbound, $maxbound) . $sr_;
        if ($text == '...') {
            $text = '';
        }

        $text = $this->highlightSearchTerm($text, $query);

        return $text;

    }

    /**
     * Hihglight search term in search results markup
     *
     * @param string $text   , search result text
     * @param string $search , the search term
     *
     * @return string
     */
    protected function highlightSearchTerm($text, $search)
    {
        return preg_replace('#' . $search . '#iu', '<span class="match">$0</span>', $text);
    }

    /**
     * @param $text
     * @return string
     */
    protected function removeAccents($text)
    {
        $transform = ['Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
            'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
            'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
            'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
            'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
            'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
            'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'];

        return strtr($text, $transform);
    }
}
