<?php
/**
Developer : surendra gupta
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Rest;

use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated
 */
class ImageController extends AbstractRestController
{
    /**
     * @Route("/image-thumbnail/id/{id}", methods={"GET"})
     * @Route("/image-thumbnail", methods={"GET"})
     *
     * @param Request     $request
     * @param string|null $id
     *
     * @return JsonResponse
     *
     * @throws ResponseException
     */
    public function imageThumbnailAction(Request $request, $id = null)
    {
        $this->checkPermission('thumbnails');

        $id = $this->resolveId($request, $id);

        $config = Config::getByName($id);
        if (!$config instanceof Config) {
            throw $this->createNotFoundResponseException(sprintf('Thumbnail "%s" doesn\'t exist', htmlentities($id)));
        }

        $data = $config->getForWebserviceExport();

        return $this->createSuccessResponse($data);
    }

    /**
     * @Route("/image-thumbnails", methods={"GET"})
     */
    public function imageThumbnailsAction()
    {
        $this->checkPermission('thumbnails');

        $thumbnails = [];

        $list = new Config\Listing();
        $items = $list->getThumbnails();

        foreach ($items as $item) {
            $thumbnails[] = [
                'id' => $item->getName(),
                'text' => $item->getName()
            ];
        }

        return $this->createCollectionSuccessResponse($thumbnails);
    }
}
