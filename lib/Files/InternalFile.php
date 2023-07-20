<?php

namespace OCA\Workspace\Files;

use OCP\Files\Storage\IStorage;

class InternalFile implements ManagerConnectionFileInterface
{
    private $resource;
        
    public function __construct(private string $path, private IStorage $store)
    {
    }

    /**
     * @return resource|false
     */
    public function open()
    {
        $this->resource =  $this->store->fopen($this->path, "r");
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
