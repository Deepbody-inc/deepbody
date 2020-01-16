<?php
/**
 Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Reports;

use Pimcore\Config;
use Pimcore\Config\ReportConfigWriter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/settings")
 */
class SettingsController extends ReportsControllerBase
{
    /**
     * @Route("/get", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $this->checkPermission('system_settings');

        // special piwik handling - as the piwik settings tab is on the same page as the other settings
        // we need to check here if we want to include the piwik config in the response
        $config = $this->getConfig()->toArray();
        if (!$this->getAdminUser()->isAllowed('piwik_settings') && isset($config['piwik'])) {
            unset($config['piwik']);
        }

        $response = [
            'values' => $config,
            'config' => []
        ];

        return $this->adminJson($response);
    }

    /**
     * @Route("/save", methods={"PUT"})
     *
     * @param Request $request
     * @param ReportConfigWriter $configWriter
     *
     * @return JsonResponse
     */
    public function saveAction(Request $request, ReportConfigWriter $configWriter)
    {
        $this->checkPermission('system_settings');

        $values = $this->decodeJson($request->get('data'));
        if (!is_array($values)) {
            $values = [];
        }

        // special piwik handling - if the user is not allowed to save piwik settings
        // force override the settings to write with the current config and ignore the
        // submitted values
        if (!$this->getAdminUser()->isAllowed('piwik_settings')) {
            $currentConfig = Config::getReportConfig()->toArray();
            $piwikConfig = $currentConfig['piwik'] ?? [];

            // override piwik settings with current config
            $values['piwik'] = $piwikConfig;
        }

        $configWriter->write($values);

        return $this->adminJson(['success' => true]);
    }
}
