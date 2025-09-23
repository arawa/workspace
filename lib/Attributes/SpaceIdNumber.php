<?php

namespace OCA\Workspace\Attribute;

use Attribute;

/**
 * All methods with this attribute will be accessible to users who are Workspace Manager of a workspace or General Manager.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class SpaceIdNumber {
}
