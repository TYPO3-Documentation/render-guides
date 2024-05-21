<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class EditOnGithubIncludeNode extends GeneralDirectiveNode
{
    public function __construct(
        private readonly string $path,
    ) {
        parent::__construct('edit-on-github-include', '', new InlineCompoundNode());
    }

    public function getPath(): string
    {
        return $this->path;
    }

}
