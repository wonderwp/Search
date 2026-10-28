<?php

declare(strict_types=1);

namespace WonderWp\Component\PluginSkeleton {
    if (!interface_exists(ManagerInterface::class, false)) {
        interface ManagerInterface
        {
        }
    }

    if (!interface_exists(ManagerAwareInterface::class, false)) {
        interface ManagerAwareInterface
        {
            public function getManager(): ManagerInterface;

            public function setManager(ManagerInterface $manager): static;
        }
    }

    if (!trait_exists(ManagerAwareTrait::class, false)) {
        trait ManagerAwareTrait
        {
            protected $manager;

            public function getManager(): ManagerInterface
            {
                return $this->manager;
            }

            public function setManager(ManagerInterface $manager): static
            {
                $this->manager = $manager;

                return $this;
            }
        }
    }
}

namespace WonderWp\Component\Service {
    if (!interface_exists(ServiceInterface::class, false)) {
        interface ServiceInterface
        {
        }
    }

    if (!class_exists(AbstractService::class, false)) {
        abstract class AbstractService implements ServiceInterface
        {
            use \WonderWp\Component\PluginSkeleton\ManagerAwareTrait;

            public function __construct($manager = null)
            {
                $this->manager = $manager;
            }
        }
    }
}
