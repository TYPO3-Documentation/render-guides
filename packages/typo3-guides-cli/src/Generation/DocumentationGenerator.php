<?php

namespace T3Docs\GuidesCli\Generation;

use T3Docs\GuidesCli\Twig\RstExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class DocumentationGenerator
{
    /**
     * @param array<string, mixed> $data
     */
    public function generate(array $data, string $templatesDir, string $outputDir, bool $enableExampleFileGeneration): void
    {
        $loader = new FilesystemLoader($templatesDir);
        $twig = new Environment($loader);
        $twig->addExtension(new RstExtension());


        // Render the XML
        $xmlContent = $twig->render('guides.xml.twig', $data);
        file_put_contents($outputDir . '/guides.xml', $xmlContent);

        if (!$enableExampleFileGeneration) {
            return;
        }

        // Generate the Documentation
        if ($data['format'] === 'md') {
            $xmlContent = $twig->render('Index.md.twig', $data);
            file_put_contents($outputDir . '/Index.md', $xmlContent);
            return;
        }
        $templatesDir = __DIR__ . '/../../resources/templates/rst';
        $files = scandir($templatesDir);

        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'twig') {
                continue;
            }

            // Determine the output filename
            $outputFileName = str_replace('.twig', '', $file);
            $outputFilePath = sprintf('%s/%s', $outputDir, $outputFileName);

            // Check if the output file already exists
            if (is_file($outputFilePath)) {
                continue;
            }

            // Render the template
            $output = $twig->render(sprintf('rst/%s', $file), $data);

            // Save the rendered content to the output file
            file_put_contents($outputFilePath, $output);
        }
    }
}
