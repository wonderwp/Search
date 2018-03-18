<?php

namespace WonderWp\Component\Search\Renderer;

abstract class SearchResultsRenderer implements SearchResultsRendererInterface
{
    /**
     * @inheritDoc
     */
    abstract public function getMarkup(array $results, $query, array $opts = []);

}
