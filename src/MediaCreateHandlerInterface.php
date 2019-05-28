<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media;

use Ixocreate\Filesystem\FilesystemInterface;

interface MediaCreateHandlerInterface
{
    public function filename(): string;

    public function tempFile(): string;

    public function mimeType(): string;

    public function fileSize(): int;

    public function fileHash(): string;

    public function move(FilesystemInterface $storage, $destination);
}
