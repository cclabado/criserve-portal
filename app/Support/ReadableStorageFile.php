<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ReadableStorageFile
{
    protected bool $cleanedUp = false;

    protected function __construct(
        protected string $path,
        protected ?string $temporaryPath = null
    ) {
    }

    public static function fromDisk(string $disk, string $path, string $missingFileMessage): self
    {
        $filesystem = Storage::disk($disk);

        if (! $filesystem->exists($path)) {
            throw new \RuntimeException($missingFileMessage);
        }

        $driver = (string) config("filesystems.disks.{$disk}.driver", '');

        if ($driver === 'local' && method_exists($filesystem, 'path')) {
            return new self($filesystem->path($path));
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $temporaryBasePath = tempnam(sys_get_temp_dir(), 'criserve-');

        if ($temporaryBasePath === false) {
            throw new \RuntimeException('Unable to create a temporary file for processing.');
        }

        $temporaryPath = $extension !== ''
            ? $temporaryBasePath.'.'.$extension
            : $temporaryBasePath;

        if ($temporaryPath !== $temporaryBasePath && ! @rename($temporaryBasePath, $temporaryPath)) {
            @unlink($temporaryBasePath);

            throw new \RuntimeException('Unable to prepare a temporary file for processing.');
        }

        $source = $filesystem->readStream($path);

        if (! is_resource($source)) {
            @unlink($temporaryPath);

            throw new \RuntimeException('Unable to read the stored file for processing.');
        }

        $target = fopen($temporaryPath, 'wb');

        if (! is_resource($target)) {
            fclose($source);
            @unlink($temporaryPath);

            throw new \RuntimeException('Unable to open a temporary file for processing.');
        }

        try {
            stream_copy_to_stream($source, $target);
        } finally {
            fclose($source);
            fclose($target);
        }

        return new self($temporaryPath, $temporaryPath);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function cleanup(): void
    {
        if ($this->cleanedUp || $this->temporaryPath === null) {
            return;
        }

        if (is_file($this->temporaryPath)) {
            @unlink($this->temporaryPath);
        }

        $this->cleanedUp = true;
    }
}
