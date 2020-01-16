<?php
/**
Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller;

/**
 * Controllers implementing this interface will be double-checked for admin authentication.
 *
 * @see AdminAuthenticationDoubleCheckListener
 */
interface DoubleAuthenticationControllerInterface
{
    /**
     * Determines if session should be checked for a valid user in authentication double check
     *
     * @return bool
     */
    public function needsSessionDoubleAuthenticationCheck();

    /**
     * Determines if token storage should be checked for a valid user in authentication double check
     *
     * @return bool
     */
    public function needsStorageDoubleAuthenticationCheck();
}
