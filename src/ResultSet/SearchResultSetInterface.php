<?php

namespace WonderWp\Component\Search\ResultSet;

use WonderWp\Component\Search\Result\SearchResultInterface;

interface SearchResultSetInterface
{

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return static
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @param string $label
     *
     * @return static
     */
    public function setLabel($label);

    /**
     * @return SearchResultInterface[]
     */
    public function getCollection();

    /**
     * @param SearchResultInterface[] $collection
     *
     * @return static
     */
    public function setCollection($collection);

    /**
     * @return int
     */
    public function getTotalCount();

    /**
     * @param int $totalCount
     *
     * @return static
     */
    public function setTotalCount($totalCount);

}
