<?php

namespace OCA\Workspace\Listener;

use OCP\Util;
use OCP\EventDispatcher\Event;
use OCA\Workspace\AppInfo\Application;
use OCP\EventDispatcher\IEventListener;
use OCA\Files\Event\LoadSidebar;

class LoadSidebarScripts implements IEventListener {
    public function handle(Event $event) :void {
        if (!($event instanceof LoadSidebar)) {
            return;
        }

        Util::addStyle(Application::APP_ID, 'workspace-style');
        Util::addScript(Application::APP_ID, 'workspace-sidebar');
    }
}