<?php
/**

 */

namespace Pimcore\Bundle\AdminBundle\EventListener\Traits;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

trait ControllerTypeTrait
{
    /**
     * Get controller of specified type
     *
     * @param FilterControllerEvent $event
     * @param string $type
     *
     * @return mixed
     */
    protected function getControllerType(FilterControllerEvent $event, $type)
    {
        $callable = $event->getController();

        if (!is_array($callable) || count($callable) === 0) {
            return null;
        }

        $controller = $callable[0];
        if ($controller instanceof $type) {
            return $controller;
        }
    }

    /**
     * Test if event controller is of the given type
     *
     * @param FilterControllerEvent $event
     * @param string $type
     *
     * @return bool
     */
    protected function isControllerType(FilterControllerEvent $event, $type)
    {
        $controller = $this->getControllerType($event, $type);

        return $controller && $controller instanceof $type;
    }
}
