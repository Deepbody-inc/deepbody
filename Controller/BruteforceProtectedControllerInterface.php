<?php
/**
Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller;

use Pimcore\Bundle\AdminBundle\EventListener\BruteforceProtectionListener;

/**
 * Tagging interface used to protect certain controllers from brute force attacks
 *
 * @see BruteforceProtectionListener
 */
interface BruteforceProtectedControllerInterface
{
}
