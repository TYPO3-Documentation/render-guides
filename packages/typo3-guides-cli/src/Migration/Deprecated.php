<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration;

/**
 * List of all Settings.cfg sections that are not covered by this converter
 */
enum Deprecated
{
    case latex_elements;
    case notify;
}
