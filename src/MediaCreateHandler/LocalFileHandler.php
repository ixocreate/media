<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\MediaCreateHandler;

use Ixocreate\Media\MediaCreateHandlerInterface;
use League\Flysystem\FilesystemInterface;

final class LocalFileHandler implements MediaCreateHandlerInterface
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var
     */
    private $filename;

    /**
     * @var bool
     */
    private $deleteAfterMove;

    /**
     * @var string
     */
    private $mimeType = null;

    /**
     * @var int
     */
    private $fileSize = null;

    /**
     * @var string
     */
    private $fileHash = null;

    /**
     * LocalFileHandler constructor.
     * @param string $file
     * @param string $filename
     * @param bool $deleteAfterMove
     */
    public function __construct(string $file, string $filename, bool $deleteAfterMove = true)
    {
        $this->file = $file;
        $this->filename = $filename;
        $this->deleteAfterMove = $deleteAfterMove;
    }

    /**
     * @return string
     */
    public function filename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function tempFile(): string
    {
        return $this->file;
    }

    /**
     * @param FilesystemInterface $storage
     * @param $destination
     * @throws \League\Flysystem\FileExistsException
     * @return bool
     */
    public function move(FilesystemInterface $storage, $destination): bool
    {
        $f = \fopen($this->file, 'r');
        $storage->writeStream($destination, $f);

        \fclose($f);
        if ($this->deleteAfterMove) {
            //get file infos before file is removed;
            $this->mimeType();
            $this->fileSize();
            $this->fileHash();

            \unlink($this->file);
        }

        return true;
    }

    /**
     * @return string
     */
    public function mimeType(): string
    {
        if ($this->mimeType === null) {
            $finfo = \finfo_open(FILEINFO_MIME_TYPE);
            $this->mimeType = \finfo_file($finfo, $this->file);
        }

        return $this->mimeType;
    }

    /**
     * @return int
     */
    public function fileSize(): int
    {
        if ($this->fileSize === null) {
            $this->fileSize = \filesize($this->file);
        }

        return $this->fileSize;
    }

    /**
     * @return string
     */
    public function fileHash(): string
    {
        if ($this->fileHash === null) {
            $this->fileHash = \hash_file('sha256', $this->file);
        }

        return $this->fileHash;
    }
}
