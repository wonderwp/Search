<?php

namespace WonderWp\Component\Search\Service;

use WonderWp\Component\Service\AbstractService;

abstract class AbstractSearchService extends AbstractService implements SearchServiceInterface
{
    /**
     * @var string
     */
    protected $name;

    /** @inheritdoc */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /** @inheritdoc */
    public function getName()
    {
        return $this->name;
    }

    /** @inheritdoc */
    abstract function getMarkup($query, array $opts = []);

}
