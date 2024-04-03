<?php

namespace T3Docs\Typo3DocsTheme\Settings;

use Symfony\Component\Console\Input\InputInterface;

final class Typo3DocsInputSettings
{
    private ?InputInterface $input = null;

    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    public function getInput(): ?InputInterface
    {
        return $this->input;
    }
}
