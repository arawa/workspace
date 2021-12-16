<?php

namespace OCA\Workspace\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Space extends Entity implements JsonSerializable {

    /** @var integer */
    protected $groupfolderId;

    /** @var string */
    protected $spaceName;

    /** @var integer */
    protected $spaceId;

    /** @var string */
    protected $colorCode;

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('groupfolder_id', 'integer');
        $this->addType('space_name', 'string');
        $this->addType('color_code', 'string');
    }

    /**
    * TODO: When it's wrote '$this->getId()', it prints well the
    * id when it created (POST). But, with GETs method, it has to
    * write $this->getSpaceId(). 
    */
    public function jsonSerialize()
    {
        return [
            'id' =>  (int)$this->getSpaceId(),
            'groupfolder_id' => (int)$this->groupfolderId,
            'space_name' => $this->spaceName,
            'color_code' => $this->colorCode,
        ];
    }
}