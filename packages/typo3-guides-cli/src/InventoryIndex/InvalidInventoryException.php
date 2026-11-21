<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\InventoryIndex;

use RuntimeException;

/**
 * An objects.inv.json file that cannot be indexed: unreadable, not JSON, or
 * not shaped like an inventory.
 */
final class InvalidInventoryException extends RuntimeException {}
