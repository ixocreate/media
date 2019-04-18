<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Package\Media\Action;

use Firebase\JWT\JWT;
use Ixocreate\Package\Admin\Config\AdminConfig;
use Ixocreate\Package\Admin\Response\ApiErrorResponse;
use Ixocreate\Application\Http\ErrorHandling\Response\NotFoundHandler;
use Ixocreate\Package\Filesystem\Storage\StorageSubManager;
use Ixocreate\Package\Media\Entity\Media;
use Ixocreate\Package\Media\MediaPaths;
use Ixocreate\Package\Media\Repository\MediaRepository;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

final class StreamAction implements MiddlewareInterface
{
    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    /**
     * @var NotFoundHandler
     */
    private $notFoundHandler;

    /**
     * @var StorageSubManager
     */
    private $storageSubManager;

    /**
     * @var AdminConfig
     */
    private $adminConfig;

    /**
     * @var FilesystemInterface
     */
    private $storage;

    /**
     * UploadAction constructor.
     *
     * @param MediaRepository $mediaRepository
     * @param NotFoundHandler $notFoundHandler
     * @param StorageSubManager $storageSubManager
     * @param AdminConfig $adminConfig
     */
    public function __construct(
        MediaRepository $mediaRepository,
        NotFoundHandler $notFoundHandler,
        StorageSubManager $storageSubManager,
        AdminConfig $adminConfig
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->notFoundHandler = $notFoundHandler;
        $this->storageSubManager = $storageSubManager;
        $this->adminConfig = $adminConfig;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @throws \Exception
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getAttribute('token');

        if (empty($token)) {
            return new ApiErrorResponse("bad_request");
        }

        if (empty($this->adminConfig->secret())) {
            return new ApiErrorResponse('Secret is not set in AdminConfig');
        }

        if (!$this->storageSubManager->has('media')) {
            return new ApiErrorResponse('Storage Config not set');
        }

        $this->storage = $this->storageSubManager->get('media');

        try {
            $jwt = JWT::decode($token, $this->adminConfig->secret(), ['HS512']);
        } catch (\Exception $e) {
            /**
             * TODO: do we want not founds or error response
             */
            return $this->notFoundHandler->process($request, $handler);
            // return new ApiErrorResponse("*", "invalid_token", 401);
        }

        /** @var Media $media */
        $media = $this->mediaRepository->find($jwt->data->mediaId);

        $mediaPath = $media->publicStatus() ? MediaPaths::PUBLIC_PATH : MediaPaths::PRIVATE_PATH;
        $storagePath = null;
        /**
         * make it work with delegator outputs
         */
        if (!empty($jwt->data->imageDefinition)) {
            $storagePath = MediaPaths::IMAGE_DEFINITION_PATH . $jwt->data->imageDefinition . '/';
        }
        $fileSize = $this->storage->getSize($mediaPath . $storagePath . $media->basePath() . $media->filename());

        $fileStream = $this->storage->readStream($mediaPath . $storagePath . $media->basePath() . $media->filename());

        /**
         * stream file
         */
        return (new Response())
            ->withHeader('Content-Type', $media->mimeType())
            ->withHeader('Content-Length', (string)$fileSize)
            ->withHeader('Content-Disposition', 'inline; filename=' . $media->filename())
            ->withBody(new Stream($fileStream));
    }
}
