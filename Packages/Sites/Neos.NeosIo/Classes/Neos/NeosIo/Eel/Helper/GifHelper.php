<?php
namespace Neos\NeosIo\Eel\Helper;

use Imagine\Image\ImagineInterface;
use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Utility\Environment;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\Unicode\Functions as UnicodeFunctions;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Exception\ImageFileException;

/**
 * Simple Eel helper that allows manipulation/interaction of (animated) GIFs
 */
class GifHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var ImagineInterface
     */
    protected $imagineService;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @param AssetInterface $image
     * @return Image
     * @throws ImageFileException
     */
    public function extractFirstFrame(AssetInterface $image = null)
    {
        if ($image === null) {
            return null;
        }
        $imagineImage = $this->imagineService->read($image->getResource()->getStream());

        $transformedImageTemporaryPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('FirstFrame-') . '.' . $image->getFileExtension();
        $imagineImage->layers()->get(0)->save($transformedImageTemporaryPathAndFilename);

        $originalResource = $image->getResource();
        $resource = $this->resourceManager->importResource($transformedImageTemporaryPathAndFilename, $originalResource->getCollectionName());
        if ($resource === false) {
            throw new ImageFileException('An error occurred while importing a generated image file as a resource.', 1477386144);
        }
        Files::unlink($transformedImageTemporaryPathAndFilename);

        $pathInfo = UnicodeFunctions::pathinfo($originalResource->getFilename());
        $resource->setFilename(sprintf('%s-first-frame.%s', $pathInfo['filename'], $image->getFileExtension()));

        return new Image($resource);
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
