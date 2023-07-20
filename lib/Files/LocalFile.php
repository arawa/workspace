<?php

namespace OCA\Workspace\Files;

class LocalFile implements ManagerConnectionFileInterface
{
    private $resource;

    public function __construct(private string $path)
    {
    }

    /**
     * @return resource|false
     */
    public function open()
    {
        $this->resource = fopen($this->path, "r");
        return $this->resource;
    }

    public function close(): bool
    {
        return fclose($this->resource);
    }

    public function getPath(): string {
        return $this->path;
    }
}
