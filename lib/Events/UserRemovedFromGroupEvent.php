<?php

namespace OCA\Workspace\Events;

use OCP\EventDispatcher\Event;
use OCP\IGroup;
use OCP\IUser;

class UserRemovedFromGroupEvent extends Event {
    public function __construct(
        private IUser $user,
        private IGroup $group,
    )
    {
    }
    
    public function getUser(): IUser {
        return $this->user;
    }

    public function getGroup(): IGroup {
        return $this->group;
    }
}
