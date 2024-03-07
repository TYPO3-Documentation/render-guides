<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration;

/**
 * Mapping of a Settings.cfg key for [html_theme_options] to the guides.xml
 * <extension> element
 */
enum HtmlThemeOptions: string
{
    case project_home = 'project-home';
    case project_contact = 'project-contact';
    case project_repository = 'project-repository';
    case project_issues = 'project-issues';
    case project_discussions = 'project-discussions';

    case use_opensearch = 'use-opensearch';

    case github_revision_msg = 'github-revision-msg';
    case github_branch = 'edit-on-github-branch';
    case github_repository = 'edit-on-github';
    case path_to_documentation_dir = 'edit-on-github-directory';
    case github_sphinx_locale = 'github-sphinx-locale';
    case github_commit_hash = 'github-commit-hash';
}
