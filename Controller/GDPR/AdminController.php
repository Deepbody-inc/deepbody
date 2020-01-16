<?php

declare(strict_types=1);

/**
 Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller\GDPR;

use Pimcore\Bundle\AdminBundle\GDPR\DataProvider\Manager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController
{
    /**
     * @Route("/get-data-providers", methods={"GET"})
     */
    public function getDataProvidersAction(Manager $manager)
    {
        $response = [];
        foreach ($manager->getServices() as $service) {
            $response[] = [
                'name' => $service->getName(),
                'jsClass' => $service->getJsClassName()
            ];
        }

        return $this->adminJson($response);
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->checkActionPermission($event, 'gdpr_data_extractor');
    }
}
