<?php
/**
 * @see https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Action\Media;

use Ixocreate\Admin\Response\ApiErrorResponse;
use Ixocreate\Admin\Response\ApiSuccessResponse;
use Ixocreate\Media\Config\MediaConfig;
use Ixocreate\Media\Entity\Media;
use Ixocreate\Media\Repository\MediaRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ChangePublicStatusAction implements MiddlewareInterface
{
    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    /**
     * @var MediaConfig
     */
    private $mediaConfig;

    /**
     * ChangePublicStatusAction constructor.
     * @param MediaConfig $mediaConfig
     * @param MediaRepository $mediaRepository
     */
    public function __construct(MediaRepository $mediaRepository, MediaConfig $mediaConfig)
    {
        $this->mediaRepository = $mediaRepository;
        $this->mediaConfig = $mediaConfig;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Media $media */
        $media = $this->mediaRepository->findOneBy(['id' => $request->getAttribute('id')]);

        if ($media === null) {
            return new ApiErrorResponse('given media Id does not exist');
        }

        if (!$this->mediaConfig->publicStatus()) {
            return new ApiErrorResponse('the publicStatus feature must be enabled');
        }

        $publicStatus = $media->publicStatus();

        if ($media->publicStatus()) {
            $publicStatus = (bool) false;
        }

        if (!$media->publicStatus()) {
            $publicStatus = (bool) true;
        }

        $media = $media->with('publicStatus', $publicStatus);
        $media = $media->with('updatedAt', new \DateTime());

        $this->mediaRepository->save($media);

        return new ApiSuccessResponse();
    }
}
