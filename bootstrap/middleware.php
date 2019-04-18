<?php
declare(strict_types=1);

use Ixocreate\Application\Http\Middleware\MiddlewareConfigurator;

/** @var MiddlewareConfigurator $middleware */
$middleware->addAction(\Ixocreate\Package\Media\Action\Image\EditorAction::class);
$middleware->addAction(\Ixocreate\Package\Media\Action\Media\DetailAction::class);
$middleware->addAction(\Ixocreate\Package\Media\Action\StreamAction::class);
$middleware->addAction(\Ixocreate\Package\Media\Action\UploadAction::class);
$middleware->addAction(\Ixocreate\Package\Media\Action\Media\UpdateAction::class);
$middleware->addAction(\Ixocreate\Package\Media\Action\Media\DeleteAction::class);
$middleware->addAction(\Ixocreate\Package\Media\Action\Media\IndexAction::class);
