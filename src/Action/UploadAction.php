<?php
/**
 * kiwi-suite/media (https://github.com/kiwi-suite/media)
 *
 * @package kiwi-suite/media
 * @see https://github.com/kiwi-suite/media
 * @copyright Copyright (c) 2010 - 2018 kiwi suite GmbH
 * @license MIT License
 */
declare(strict_types=1);

namespace KiwiSuite\Media\Action;

use KiwiSuite\Admin\Entity\User;
use KiwiSuite\CommandBus\CommandBus;
use KiwiSuite\Media\Command\CreateCommand;
use KiwiSuite\Media\MediaCreateHandler\UploadHandler;
use KiwiSuite\Admin\Response\ApiErrorResponse;
use KiwiSuite\Admin\Response\ApiSuccessResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\UploadedFile;

final class UploadAction implements MiddlewareInterface
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * UploadAction constructor.
     * @param CommandBus $commandBus
     */
    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!\array_key_exists('file', $request->getUploadedFiles())) {
            return new ApiErrorResponse("invalid_file");
        }

        $upload = $request->getUploadedFiles()['file'];

        if (!($upload instanceof UploadedFile)) {
            return new ApiErrorResponse("invalid_file");
        }

        /** @var CreateCommand $createCommand */
        $createCommand = $this->commandBus->create(CreateCommand::class, []);

        $handler = new UploadHandler($upload);
        $createCommand = $createCommand->withMediaCreateHandler($handler);

        $user = $request->getAttribute(User::class);
        $createCommand = $createCommand->withCreatedUser($user);

        $commandResult = $this->commandBus->dispatch($createCommand);

        if (!$commandResult->isSuccessful()) {
            return new ApiErrorResponse('media_create_media', $commandResult->messages());
        }

        return new ApiSuccessResponse(['id' => $createCommand->uuid()]);
    }
}
