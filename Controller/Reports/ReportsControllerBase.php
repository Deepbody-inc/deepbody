<?php
/**
 Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Reports;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Config;

class ReportsControllerBase extends AdminController
{
    /**
     * @return \Pimcore\Config\Config
     */
    public function getConfig()
    {
        return Config::getReportConfig();
    }
}
