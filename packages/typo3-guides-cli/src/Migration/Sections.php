<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration;

/**
 * List of all Settings.cfg sections that are converted
 */
enum Sections
{
    case html_theme_options;
    case general;
    case intersphinx_mapping;

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(
            static fn(self $section): string => $section->name,
            self::cases()
        );
    }
}
