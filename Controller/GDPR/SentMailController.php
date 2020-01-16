<?php
/**
 Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller\GDPR;

use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Model\Tool\Email\Log;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SentMailController
 *
 * @Route("/sent-mail")
 *
 * @package GDPRDataExtractorBundle\Controller
 */
class SentMailController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController
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
     *
     * @return JsonResponse
     *
     * @Route("/export", methods={"GET"})
     */
    public function exportDataObjectAction(Request $request)
    {
        $this->checkPermission('emails');

        $sentMail = Log::getById($request->get('id'));

        $sentMailArray = (array)$sentMail;
        $sentMailArray['htmlBody'] = $sentMail->getHtmlLog();
        $sentMailArray['textBody'] = $sentMail->getTextLog();

        $json = $this->encodeJson($sentMailArray, [], JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);
        $jsonResponse = new JsonResponse($json, 200, [
            'Content-Disposition' => 'attachment; filename="export-mail-' . $sentMail->getId() . '.json"'
        ], true);

        return $jsonResponse;
    }
}
