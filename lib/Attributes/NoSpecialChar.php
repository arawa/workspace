<?php

namespace OCA\Workspace\Attribute;

use Attribute;

/**
 * All methods with this attibute will reject names containing special characters.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class NoSpecialChar {
}
