<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Package\Action;

use Ixocreate\Admin\Package\Entity\User;
use Ixocreate\CommandBus\CommandBus;
use Ixocreate\Media\Package\Command\Media\CreateCommand;
use Ixocreate\Media\Package\MediaCreateHandler\UploadHandler;
use Ixocreate\Admin\Package\Response\ApiErrorResponse;
use Ixocreate\Admin\Package\Response\ApiSuccessResponse;
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
     * @throws \Exception
     * @return ResponseInterface
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
