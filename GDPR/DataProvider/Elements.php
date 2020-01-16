<?php
/**

 */
declare(strict_types=1);

namespace Pimcore\Bundle\AdminBundle\GDPR\DataProvider;

abstract class Elements implements DataProviderInterface
{
    /**
     * @param string $query
     *
     * @return string
     */
    protected function prepareQueryString($query): string
    {
        if ($query == '*') {
            $query = '';
        }

        $query = str_replace('%', '*', $query);
        $query = str_replace('@', '#', $query);
        $query = preg_replace("@([^ ])\-@", '$1 ', $query);

        return $query;
    }
}
