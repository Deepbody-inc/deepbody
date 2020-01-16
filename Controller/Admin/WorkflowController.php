<?php
/**
 Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete as ConcreteObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tool\Console;
use Pimcore\Workflow\ActionsButtonService;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Place\StatusInfo;
use Pimcore\Workflow\Transition;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;

/**
 * @Route("/workflow")
 */
class WorkflowController extends AdminController implements EventedControllerInterface
{
    /**
     * @var Document|Asset|ConcreteObject $element
     */
    private $element;

    /**
     * Returns a JSON of the available workflow actions to the admin panel
     *
     * @Route("/get-workflow-form")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getWorkflowFormAction(Request $request, Manager $workflowManager)
    {
        try {
            $workflow = $workflowManager->getWorkflowIfExists($this->element, (string) $request->get('workflowName'));
            $workflowConfig = $workflowManager->getWorkflowConfig((string) $request->get('workflowName'));

            if (empty($workflow) || empty($workflowConfig)) {
                $wfConfig = [
                    'message' => 'workflow not found'
                ];
            } else {

                //this is the default returned workflow data
                $wfConfig = [
                    'message' => '',
                    'notes_enabled' => false,
                    'notes_required' => false,
                    'additional_fields' => []
                ];

                $enabledTransitions = $workflow->getEnabledTransitions($this->element);
                /**
                 * @var Transition $transition
                 */
                $transition = null;
                foreach ($enabledTransitions as $_transition) {
                    if ($_transition->getName() === $request->get('transitionName')) {
                        $transition = $_transition;
                    }
                }

                if (empty($transition)) {
                    $wfConfig['message'] = sprintf('transition %s currently not allowed', (string) $request->get('transitionName'));
                } else {
                    $wfConfig['notes_required'] = $transition->getNotesCommentRequired();
                    $wfConfig['additional_fields'] = [];
                }
            }
        } catch (\Exception $e) {
            $wfConfig['message'] = $e->getMessage();
        }

        return $this->adminJson($wfConfig);
    }

    /**
     * @Route("/submit-workflow-transition", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function submitWorkflowTransitionAction(Request $request, Registry $workflowRegistry, Manager $workflowManager)
    {
        $workflowOptions = $request->get('workflow', []);
        $workflow = $workflowRegistry->get($this->element, $request->get('workflowName'));

        if ($workflow->can($this->element, $request->get('transition'))) {
            try {
                $workflowManager->applyWithAdditionalData($workflow, $this->element, $request->get('transition'), $workflowOptions, true);

                $data = [
                    'success' => true,
                    'callback' => 'reloadObject'
                ];
            } catch (ValidationException $e) {
                $reason = '';
                if (sizeof((array)$e->getSubItems()) > 0) {
                    $reason = '<ul>' . implode('', array_map(function ($item) {
                        return '<li>' . $item . '</li>';
                    }, $e->getSubItems())) . '</ul>';
                }

                $data = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'reason' => $reason

                ];
            } catch (\Exception $e) {
                $data = [
                    'success' => false,
                    'message' => 'error performing action on this element',
                    'reason' => $e->getMessage()
                ];
            }
        } else {
            $data = [
                'success' => false,
                'message' => 'error validating the action on this element, element cannot peform this action',
                'reason' => 'transition is currently not allowed'
            ];
        }

        return $this->adminJson($data);
    }

    /**
     * @Route("/submit-global-action", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function submitGlobalAction(Request $request, Registry $workflowRegistry, Manager $workflowManager)
    {
        $workflowOptions = $request->get('workflow', []);
        $workflow = $workflowRegistry->get($this->element, $request->get('workflowName'));

        try {
            $workflowManager->applyGlobalAction($workflow, $this->element, $request->get('transition'), $workflowOptions, true);

            $data = [
                'success' => true,
                'callback' => 'reloadObject'
            ];
        } catch (ValidationException $e) {
            $reason = '';
            if (sizeof((array)$e->getSubItems()) > 0) {
                $reason = '<ul>' . implode('', array_map(function ($item) {
                    return '<li>' . $item . '</li>';
                }, $e->getSubItems())) . '</ul>';
            }

            $data = [
                'success' => false,
                'message' => $e->getMessage(),
                'reason' => $reason

            ];
        } catch (\Exception $e) {
            $data = [
                'success' => false,
                'message' => 'error performing action on this element',
                'reason' => $e->getMessage()
            ];
        }

        return $this->adminJson($data);
    }

    /**
     * Returns the JSON needed by the workflow elements detail tab store
     *
     * @Route("/get-workflow-details")
     *
     * @param Request $request
     * @param Manager $workflowManager
     * @param StatusInfo $placeStatusInfo
     * @param RouterInterface $router
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getWorkflowDetailsStore(Request $request, Manager $workflowManager, StatusInfo $placeStatusInfo, RouterInterface $router, ActionsButtonService $actionsButtonService)
    {
        $data = [];

        foreach ($workflowManager->getAllWorkflowsForSubject($this->element) as $workflow) {
            $workflowConfig = $workflowManager->getWorkflowConfig($workflow->getName());

            $svg = null;
            $msg = '';
            try {
                $svg = $this->getWorkflowSvg($workflow);
            } catch (\InvalidArgumentException $e) {
                $msg = $e->getMessage();
            }

            $url = $router->generate(
                'pimcore_admin_workflow_show_graph',
                [
                    'cid' => $request->get('cid'),
                    'ctype' => $request->get('ctype'),
                    'workflow' => $workflow->getName()
                ]
            );

            $allowedTransitions = $actionsButtonService->getAllowedTransitions($workflow, $this->element);
            $globalActions = $actionsButtonService->getGlobalActions($workflow, $this->element);

            $data[] = [
                'workflowName' => $workflowConfig->getLabel(),
                'placeInfo' => $placeStatusInfo->getAllPalacesHtml($this->element, $workflow->getName()),
                'graph' => $msg ?: '<a href="' . $url .'" target="_blank"><div class="workflow-graph-preview">'.$svg.'</div></a>',
                'allowedTransitions' => $allowedTransitions,
                'globalActions' => $globalActions
            ];
        }

        return $this->adminJson([
            'data' => $data,
            'success' => true,
            'total' => sizeof($data)
        ]);
    }

    /**
     * Returns the JSON needed by the workflow elements detail tab store
     *
     * @Route("/show-graph", name="pimcore_admin_workflow_show_graph")
     *
     * @param Request $request
     * @param Manager $workflowManager
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function showGraph(Request $request, Manager $workflowManager)
    {
        $workflow = $workflowManager->getWorkflowByName($request->get('workflow'));

        $response = new Response($this->getWorkflowSvg($workflow));
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }

    /**
     * @param Workflow $workflow
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getWorkflowSvg(Workflow $workflow)
    {
        $marking = $workflow->getMarking($this->element);

        $php = Console::getExecutable('php');
        $dot = Console::getExecutable('dot');

        if (!$php) {
            throw new \InvalidArgumentException($this->trans('workflow_cmd_not_found', ['php']));
        }

        if (!$dot) {
            throw new \InvalidArgumentException($this->trans('workflow_cmd_not_found', ['dot']));
        }

        $cmd = sprintf('%s %s/bin/console pimcore:workflow:dump %s %s | %s -Tsvg',
            $php,
            PIMCORE_PROJECT_ROOT,
            $workflow->getName(),
            implode(' ', array_keys($marking->getPlaces())),
            $dot
        );

        return Console::exec($cmd);
    }

    /**
     * @param  Document|Asset|ConcreteObject $element
     *
     * @return Document|Asset|ConcreteObject
     */
    protected function getLatestVersion($element)
    {
        if (
            $element instanceof Document\Folder
            || $element instanceof Asset\Folder
            || $element instanceof DataObject\Folder
            || $element instanceof Document\Hardlink
            || $element instanceof Document\Link
        ) {
            return $element;
        }

        //TODO move this maybe to a service method, since this is also used in DataObjectController and DocumentControllers
        if ($element instanceof Document) {
            $latestVersion = $element->getLatestVersion();
            if ($latestVersion) {
                $latestDoc = $latestVersion->loadData();
                if ($latestDoc instanceof Document) {
                    $element = $latestDoc;
                    $element->setModificationDate($element->getModificationDate());
                }
            }
        }

        if ($element instanceof DataObject\Concrete) {
            $modificationDate = $element->getModificationDate();
            $latestVersion = $element->getLatestVersion();
            if ($latestVersion) {
                $latestObj = $latestVersion->loadData();
                if ($latestObj instanceof ConcreteObject) {
                    $element = $latestObj;
                    $element->setModificationDate($modificationDate);
                }
            }
        }

        return $element;
    }

    /**
     * @param FilterControllerEvent $event
     *
     * @throws \Exception
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        $request = $event->getRequest();

        if ($request->get('ctype') === 'document') {
            $this->element = Document::getById((int) $request->get('cid', 0));
        } elseif ($request->get('ctype') === 'asset') {
            $this->element = Asset::getById((int) $request->get('cid', 0));
        } elseif ($request->get('ctype') === 'object') {
            $this->element = ConcreteObject::getById((int) $request->get('cid', 0));
        }

        if (!$this->element) {
            throw new \Exception('Cannot load element' . $request->get('cid') . ' of type \'' . $request->get('ctype') . '\'');
        }

        //get the latest available version of the element -
        $this->element = $this->getLatestVersion($this->element);
        $this->element->setUserModification($this->getAdminUser()->getId());
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}