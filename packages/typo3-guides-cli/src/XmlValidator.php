<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli;

use Symfony\Component\Console\Output\OutputInterface;

final class XmlValidator
{
    /**
     * @param string $xmlFilePath
     * @param string $xsdFilePath
     * @param array<int, string> $errors
     */
    public function __construct(
        private readonly string $xmlFilePath,
        private readonly string $xsdFilePath,
        private array $errors = []
    ) {}

    public function validate(): bool
    {
        try {
            $dom = new \DOMDocument();
        } catch (\Exception) {
            $this->errors[] = '<error>DOMDocument failed</error> to initialize.';
            return false;
        }

        libxml_use_internal_errors(true);

        // Custom error handler function
        set_error_handler([$this, 'errorHandler']);

        try {
            $dom->load($this->xmlFilePath);
        } catch (\Exception) {
            restore_error_handler();
            $this->errors[] = sprintf('- <error>%s</error> failed to be loaded as XML.', $this->xmlFilePath);
            return false;
        }

        // Validate against the XSD schema
        if (!$dom->schemaValidate($this->xsdFilePath)) {
            restore_error_handler();
            $this->errors[] = sprintf('- <error>%s</error> failed validation.', $this->xmlFilePath);

            // Get and display detailed error information
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $this->errors[] = sprintf(
                    '  * Line %d, Column %d: %s',
                    $error->line,
                    $error->column,
                    trim($error->message)
                );
            }

            // Clear libxml errors
            libxml_clear_errors();

            return false;
        }

        // Restore the default error handler
        restore_error_handler();

        return true;
    }

    // Custom error handler function within the class
    private function errorHandler(mixed $errno, string $errstr): void
    {
        $this->errors[] = 'xxx' . $errno . ': ' . $errstr;
    }

    public function showErrors(OutputInterface $output): void
    {
        foreach ($this->errors as $error) {
            $output->writeln($error);
        }
    }
}
