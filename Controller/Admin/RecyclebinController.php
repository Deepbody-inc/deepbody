<?php
/**
 Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Model\Element;
use Pimcore\Model\Element\Recyclebin;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

class RecyclebinController extends AdminController implements EventedControllerInterface
{
    /**
     * @Route("/recyclebin/list", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        if ($request->get('xaction') == 'destroy') {
            $item = Recyclebin\Item::getById(\Pimcore\Bundle\AdminBundle\Helper\QueryParams::getRecordIdForGridRequest($request->get('data')));
            $item->delete();

            return $this->adminJson(['success' => true, 'data' => []]);
        } else {
            $db = \Pimcore\Db::get();

            $list = new Recyclebin\Item\Listing();
            $list->setLimit($request->get('limit'));
            $list->setOffset($request->get('start'));

            $list->setOrderKey('date');
            $list->setOrder('DESC');

            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }

            $conditionFilters = [];

            if ($request->get('filterFullText')) {
                $conditionFilters[] = 'path LIKE ' . $list->quote('%'.$request->get('filterFullText').'%');
            }

            $filters = $request->get('filter');
            if ($filters) {
                $filters = $this->decodeJson($filters);

                foreach ($filters as $filter) {
                    $operator = '=';

                    $filterField = $filter['property'];
                    $filterOperator = $filter['operator'];

                    if ($filter['type'] == 'string') {
                        $operator = 'LIKE';
                    } elseif ($filter['type'] == 'numeric') {
                        if ($filterOperator == 'lt') {
                            $operator = '<';
                        } elseif ($filterOperator == 'gt') {
                            $operator = '>';
                        } elseif ($filterOperator == 'eq') {
                            $operator = '=';
                        }
                    } elseif ($filter['type'] == 'date') {
                        if ($filterOperator == 'lt') {
                            $operator = '<';
                        } elseif ($filterOperator == 'gt') {
                            $operator = '>';
                        } elseif ($filterOperator == 'eq') {
                            $operator = '=';
                        }
                        $filter['value'] = strtotime($filter['value']);
                    } elseif ($filter['type'] == 'list') {
                        $operator = '=';
                    } elseif ($filter['type'] == 'boolean') {
                        $operator = '=';
                        $filter['value'] = (int) $filter['value'];
                    }
                    // system field
                    $value = $filter['value'];
                    if ($operator == 'LIKE') {
                        $value = '%' . $value . '%';
                    }

                    $field = '`' . $filterField . '` ';
                    if ($filter['field'] == 'fullpath') {
                        $field = 'CONCAT(path,filename)';
                    }

                    if ($filter['type'] == 'date' && $operator == '=') {
                        $maxTime = $value + (86400 - 1); //specifies the top point of the range used in the condition
                        $condition = $field . ' BETWEEN ' . $db->quote($value) . ' AND ' . $db->quote($maxTime);
                        $conditionFilters[] = $condition;
                    } else {
                        $conditionFilters[] = $field . $operator . " '" . $value . "' ";
                    }
                }
            }

            if (!empty($conditionFilters)) {
                $condition = implode(' AND ', $conditionFilters);
                $list->setCondition($condition);
            }

            $items = $list->load();

            return $this->adminJson(['data' => $items, 'success' => true, 'total' => $list->getTotalCount()]);
        }
    }

    /**
     * @Route("/recyclebin/restore", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function restoreAction(Request $request)
    {
        $item = Recyclebin\Item::getById($request->get('id'));
        $item->restore();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/recyclebin/flush", methods={"DELETE"})
     *
     * @return JsonResponse
     */
    public function flushAction()
    {
        $bin = new Element\Recyclebin();
        $bin->flush();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/recyclebin/add", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAction(Request $request)
    {
        $element = Element\Service::getElementById($request->get('type'), $request->get('id'));

        if ($element) {
            $type = Element\Service::getElementType($element);
            $baseClass = Element\Service::getBaseClassNameForElement($type);
            $listClass = '\\Pimcore\\Model\\' . $baseClass . '\\Listing';
            $list = new $listClass();
            $list->setCondition((($type == 'object') ? 'o_' : '') . 'path LIKE ' . $list->quote($element->getRealFullPath() . '/%'));
            $children = $list->getTotalCount();

            if ($children <= 100) {
                Recyclebin\Item::create($element, $this->getAdminUser());
            }

            return $this->adminJson(['success' => true]);
        } else {
            return $this->adminJson(['success' => false]);
        }
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

        // recyclebin actions might take some time (save & restore)
        $timeout = 600; // 10 minutes
        @ini_set('max_execution_time', $timeout);
        set_time_limit($timeout);

        // check permissions
        $this->checkActionPermission($event, 'recyclebin', ['addAction']);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
