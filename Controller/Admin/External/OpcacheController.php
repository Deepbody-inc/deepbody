<?php
/**
 Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\External;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\EventedControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Annotation\Route;

class OpcacheController extends AdminController implements EventedControllerInterface
{
    /**
     * @Route("/external_opcache")
     *
     * @param Request $request
     * @param Profiler $profiler
     *
     * @return Response
     */
    public function indexAction(Request $request, ?Profiler $profiler)
    {
        if ($profiler) {
            $profiler->disable();
        }

        $path = PIMCORE_COMPOSER_PATH . '/amnuts/opcache-gui';

        ob_start();
        include($path . '/index.php');
        $content = ob_get_clean();

        return new Response($content);
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        // only for admins
        $this->checkPermission('opcache');
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}