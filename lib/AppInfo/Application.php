<?php

namespace OCA\Workspace\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
        public const APP_ID = 'workspace';

        public function __construct() {
                parent::__construct(self::APP_ID);
        }
}

