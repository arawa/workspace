<?php

namespace OCA\Workspace\Listener;

use OCA\Files\Event\LoadSidebar;
use OCA\Workspace\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/**
 * @template-implements IEventListener<Event>
 */
class LoadSidebarScripts implements IEventListener {
    public function handle(Event $event): void {
        if (!($event instanceof LoadSidebar)) {
            return;
        }

        Util::addStyle(Application::APP_ID, 'workspace-style');
        Util::addScript(Application::APP_ID, 'workspace-sidebar');
    }
}
