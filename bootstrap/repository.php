<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media;

use Ixocreate\Database\Repository\RepositoryConfigurator;
use Ixocreate\Media\Repository\MediaCreatedRepository;
use Ixocreate\Media\Repository\MediaImageInfoRepository;
use Ixocreate\Media\Repository\MediaRepository;

/** @var RepositoryConfigurator $repository */
$repository->addRepository(MediaRepository::class);
$repository->addRepository(MediaImageInfoRepository::class);
$repository->addRepository(MediaCreatedRepository::class);
