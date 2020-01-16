<?php
/**

 */
declare(strict_types=1);

namespace Pimcore\Bundle\AdminBundle\GDPR\DataProvider;

interface DataProviderInterface
{
    /**
     * Returns sort priority - higher is sorted first
     *
     * @return int
     */
    public function getSortPriority(): int;

    /**
     * Returns name of DataProvider
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns JavaScript class name of frontend implementation
     *
     * @return string
     */
    public function getJsClassName(): string;
}
