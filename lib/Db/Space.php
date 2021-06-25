<?php

namespace OCA\Workspace\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Space extends Entity implements JsonSerializable {

    protected $groupfolderId;
    protected $spaceName;
    protected $spaceId;

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('groupfolder_id', 'integer');
        $this->addType('space_name', 'string');
    }

    /**
    * TODO: When it's wrote '$this->getId()', it prints well the
    * id when it created (POST). But, with GETs method, it has to
    * write $this->getSpaceId(). 
    */
    public function jsonSerialize()
    {
        return [
            $this->getSpaceId() => [
                'id' =>  $this->getSpaceId(),
                'groupfolder_id' => $this->groupfolderId,
                'space_name' => $this->spaceName,
            ]
        ];
    }

}