<?php
/**
 Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Tool\Requirements;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/install")
 */
class InstallController extends AdminController
{
    /**
     * @Route("/check", methods={"GET", "POST"})
     *
     * @param Request $request
     * @param ConnectionInterface $db
     * @param Profiler $profiler
     *
     * @return Response
     */
    public function checkAction(Request $request, ConnectionInterface $db, ?Profiler $profiler)
    {
        if ($profiler) {
            $profiler->disable();
        }

        $checksPHP = Requirements::checkPhp();
        $checksFS = Requirements::checkFilesystem();
        $checksApps = Requirements::checkExternalApplications();
        $checksMySQL = Requirements::checkMysql($db);

        $viewParams = [
            'checksApps' => $checksApps,
            'checksPHP' => $checksPHP,
            'checksMySQL' => $checksMySQL,
            'checksFS' => $checksFS,
            'headless' => (bool)$request->get('headless')
        ];

        return $this->render('PimcoreAdminBundle:Admin/Install:check.html.twig', $viewParams);
    }
}
