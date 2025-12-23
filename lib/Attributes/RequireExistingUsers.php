<?php

namespace OCA\Workspace\Attribute;

use Attribute;

/**
 * This attribute indicates that the method should verify
 * whether the users specified in a UID list exist.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class RequireExistingUsers {
}
