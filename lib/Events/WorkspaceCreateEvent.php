<?php

namespace OCA\Workspace\Events;

use OCA\GroupFolders\Folder\FolderManager;
use OCA\Workspace\Db\Space;
use OCP\EventDispatcher\Event;
use OCP\IGroup;

class WorkspaceCreateEvent extends Event {
    public function __construct(private Space $space,
        private IGroup $workspaceManagerGroup,
        private IGroup $workspaceUserGroup)
    {
        parent::__construct();
    }

    public function getSpace(): Space
    {
        return $this->space;
    }

    public function getWorkspaceManagerGroup(): IGroup
    {
        return $this->workspaceManagerGroup;
    }

    public function getWorkspaceUserGroup(): IGroup
    {
        return $this->workspaceUserGroup;
    }
}
