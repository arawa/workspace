<?php

namespace OCA\Workspace\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Space extends Entity implements JsonSerializable {

    protected $groupfolderId;
    protected $spaceName;

    public function __construct()
    {
        $this->addType('space_id', 'integer');
    }

    public function jsonSerialize()
    {
        return [
            'space_id' =>  $this->getId(),
            'groupfolder_id' => $this->groupfolderId,
            'space_name' => $this->spaceName,
        ];
    }

    public function finAll()
    {
        return "";
    }
}