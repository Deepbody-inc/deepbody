<?php
/**

 */
declare(strict_types=1);

namespace Pimcore\Bundle\AdminBundle\GDPR\DataProvider;

class SentMail implements DataProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'sentMail';
    }

    /**
     * @inheritdoc
     */
    public function getJsClassName(): string
    {
        return 'pimcore.settings.gdpr.dataproviders.sentMail';
    }

    /**
     * @inheritdoc
     */
    public function getSortPriority(): int
    {
        return 20;
    }
}
