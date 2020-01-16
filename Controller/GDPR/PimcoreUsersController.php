<?php
/**
 Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller\GDPR;

use Pimcore\Bundle\AdminBundle\GDPR\DataProvider\PimcoreUsers;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PimcoreUsersController
 *
 * @Route("/pimcore-users")
 *
 * @package GDPRDataExtractorBundle\Controller
 */
class PimcoreUsersController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController
{
    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        $this->checkActionPermission($event, 'gdpr_data_extractor');
    }

    /**
     * @param Request $request
     * @param PimcoreUsers $pimcoreUsers
     *
     * @return JsonResponse
     *
     * @Route("/search-users", methods={"GET"})
     */
    public function searchUsersAction(Request $request, PimcoreUsers $pimcoreUsers)
    {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $result = $pimcoreUsers->searchData(
            intval($allParams['id']),
            strip_tags($allParams['firstname']),
            strip_tags($allParams['lastname']),
            strip_tags($allParams['email']),
            intval($allParams['start']),
            intval($allParams['limit']),
            $allParams['sort']
        );

        return $this->adminJson($result);
    }

    /**
     * @param Request $request
     * @param PimcoreUsers $pimcoreUsers
     * @Route("/export-user-data", methods={"GET"})
     *
     * @return \Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse
     */
    public function exportUserDataAction(Request $request, PimcoreUsers $pimcoreUsers)
    {
        $this->checkPermission('users');
        $userData = $pimcoreUsers->getExportData(intval($request->get('id')));

        $json = $this->encodeJson($userData, [], JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);
        $jsonResponse = new JsonResponse($json, 200, [
            'Content-Disposition' => 'attachment; filename="export-userdata-' . $userData['id'] . '.json"'
        ], true);

        return $jsonResponse;
    }
}
