<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration;

/**
 * Mapping of a Settings.cfg key for [general] to the XML <project> element
 */
enum Project: string
{
    case project = 'title';
    case release = 'release';
    case version = 'version';
    case copyright = 'copyright';
}
