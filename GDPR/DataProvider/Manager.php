<?php
/**

 */
declare(strict_types=1);

namespace Pimcore\Bundle\AdminBundle\GDPR\DataProvider;

use Pimcore\DependencyInjection\CollectionServiceLocator;

class Manager
{
    /**
     * @var CollectionServiceLocator
     */
    private $services;

    /**
     * @var array
     */
    private $sortedServices;

    public function __construct(CollectionServiceLocator $services)
    {
        $this->services = $services;
    }

    /**
     * Returns registered services in sorted order
     *
     * @return DataProviderInterface[]
     */
    public function getServices(): array
    {
        if (null !== $this->sortedServices) {
            return $this->sortedServices;
        }

        $this->sortedServices = $this->services->all();

        usort($this->sortedServices, function (DataProviderInterface $left, DataProviderInterface $right) {
            return $left->getSortPriority() > $right->getSortPriority();
        });

        return $this->sortedServices;
    }
}
