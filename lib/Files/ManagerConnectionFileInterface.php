<?php

namespace OCA\Workspace\Files;

interface ManagerConnectionFileInterface
{
    public function open();
    public function close();
}
