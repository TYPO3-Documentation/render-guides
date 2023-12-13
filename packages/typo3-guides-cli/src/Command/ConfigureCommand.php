<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Command;

use PHPStan\Command\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ConfigureCommand extends Command
{
    protected static $defaultName = 'configure';

    /** @var \DOMDocument Holds the XML document that will be written (guides.xml) */
    private \DOMDocument $xmlDocument;

    protected function configure(): void
    {
        $this->setDescription('Configure guides.xml attributes programmatically.');
        $this->setHelp(
            <<<'EOT'
                The <info>%command.name%</info> command helps to set configuration options
                and attributes of the project to be rendered. These options are saved
                in a file <info>guides.xml</info>.
                You can use this CLI instead of manually editing the xml file.

                A complex example:

                <info>$php %command.name% \
                --project-version="13.37" \
                --project-title="My project title" \
                --project-release="main" \
                --project-copyright="The World" \
                \
                --inventory-id="h2document" \
                --inventory-url="https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/" \
                --inventory-id="t3install" \
                --inventory-url="https://docs.typo3.org/m/typo3/guide-installation/main/en-us/" \
                \
                --extension-class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" \
                --extension-attribute="edit-on-github-branch" \
                --extension-value="draft" \
                \
                --extension-class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" \
                --extension-attribute="edit-on-github" \
                --extension-value="https://github.com/vendor/extension" \
                \
                --extension-class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" \
                --extension-attribute="project-contact" \
                --extension-value="mailto:mail@example.com" \
                \
                --extension-class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension" \
                --extension-attribute="project-home" \
                --extension-value="https://www.typo3.org" \
                \
                --output-format=html \
                --output-format=singlepage \
                --output-format=interlink \
                \
                --guides-links-are-relative="true" \
                --guides-theme="typo3docs"
                \
                Documentation/</info>

                A simple example:

                <info>$ php %command.name% --project-release="draft" --project-version="draft" /path/to/directory</info>

                EOT
        );
        $this->setDefinition([
            new InputOption(
                'project-version',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set project version (<info>guides.project[version]</info>)'
            ),
            new InputOption(
                'project-title',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set project title (<info>guides.project[title]</info>)'
            ),
            new InputOption(
                'project-release',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set project title (<info>guides.project[release]</info>)'
            ),
            new InputOption(
                'project-copyright',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set project title (<info>guides.project[copyright]</info>)'
            ),

            new InputOption(
                'inventory-id',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Add/modify inventory interlink entry item (<info>guides.inventory[id]</info>), needs inventory-url too.'
            ),
            new InputOption(
                'inventory-url',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Add/modify inventory interlink entry URL value (<info>guides.inventory[url]</info>), needs inventory-id too.'
            ),

            /** This seems uninterpreted at the moment? It is listed in the XSD, but I see no examples.
            new InputOption(
                'theme-extends',
                null,
                InputOption::VALUE_OPTIONAL,
                'Add/modify theme value (<info>guides.theme[extends]</info>), needs theme-template-* too.'
            ),

            new InputOption(
                'theme-template-file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Add/modify theme value (<info>guides.theme.template[file]</info>), needs theme-extends too.'
            ),
            new InputOption(
                'theme-template-node',
                null,
                InputOption::VALUE_OPTIONAL,
                'Add/modify theme value (<info>guides.theme.template[node]</info>), needs theme-extends too.'
            ),
            new InputOption(
                'theme-template-format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Add/modify theme value (<info>guides.theme.template[format]</info>), needs theme-extends too.'
            ),
            */

            new InputOption(
                'extension-class',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Add/modify extension with specified class (<info>guides.extension[class]</info>), needs extension-attribute and extension-value too.'
            ),
            new InputOption(
                'extension-attribute',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Add/modify extension with attribute (<info>guides.extension[ATTRIBUTE]</info>), needs extension-value too.'
            ),
            new InputOption(
                'extension-value',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Add/modify extension with attribute (<info>guides.extension[VALUE]</info>), needs extension-attribute too.'
            ),

            /** This seems uninterpreted at the moment? It is listed in the XSD, but I see no examples.
            new InputOption(
                'base-template-path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set base-template-path (<info>guides.base-template-path</info>)'
            ),
            */

            new InputOption(
                'output-format',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Set output-format (<info>guides.output-format</info>).'
            ),

            new InputOption(
                'guides-input',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set input (<info>guides[input]</info>)'
            ),
            new InputOption(
                'guides-input-file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set input-file (<info>guides[input-file]</info>)'
            ),
            new InputOption(
                'guides-input-format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set input-format (<info>guides[input-format]</info>)'
            ),
            new InputOption(
                'guides-output',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set output (<info>guides[output]</info>)'
            ),
            new InputOption(
                'guides-log-path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set log-path (<info>guides[log-path]</info>)'
            ),
            new InputOption(
                'guides-fail-on-log',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set fail-on-log (<info>guides[fail-on-log]</info>)'
            ),
            new InputOption(
                'guides-show-progress',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set show-progress (<info>guides[show-progress]</info>)'
            ),
            new InputOption(
                'guides-theme',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set theme (<info>guides[theme]</info>)'
            ),
            new InputOption(
                'guides-default-code-language',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set default-code-language (<info>guides[default-code-language]</info>)'
            ),
            new InputOption(
                'guides-links-are-relative',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set relative link output (true/false) (<info>guides[links-are-relative]</info>)'
            ),

            new InputArgument(
                'input',
                InputArgument::OPTIONAL,
                'Path to directory where <info>guides.xml</info> will be modified/created.',
                './'
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($output->isVeryVerbose()) {
            $output->writeln('Specified <info>arguments and options</info>:');
            $output->writeln(print_r($input->getArguments(), true));
            $output->writeln(print_r($input->getOptions(), true));
        }

        $config = $input->getArgument('input') . '/guides.xml';

        if ($output->isVeryVerbose()) {
            $output->writeln(sprintf('Config: <info>%s</info>', $config));
        }

        if (!file_exists($config)) {
            if ($output->isVeryVerbose()) {
                $output->writeln('Creating fresh file.');
            }

            if (!$this->createEmptyGuides($config, $output)) {
                $output->writeln('<error>Could not create guides.xml in specified directory.</error>');
                return Command::FAILURE;
            }
        }

        if (!$this->operateOnXml($config, $input, $output)) {
            $output->writeln('<error>Could not alter guides.xml in specified directory.</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function operateOnXmlProject(\SimpleXMLElement $xml, InputInterface $input, OutputInterface $output): bool
    {
        $projectVariables = [
            'version'   => $input->getOption('project-version'),
            'release'   => $input->getOption('project-release'),
            'title'     => $input->getOption('project-title'),
            'copyright' => $input->getOption('project-copyright'),
        ];

        $projectElement = null;
        foreach ($projectVariables as $projectAttribute => $projectValue) {
            if ($projectValue === null) {
                continue;
            }

            // To be discussed
            if (!is_string($projectValue)) {
                continue;
            }

            $projectAttribute = $this->trimForXml($projectAttribute);
            $projectValue = $this->trimForXml($projectValue);

            if ($projectElement === null) {
                $elements = $xml->xpath('/ns:guides/ns:project');
                if ($elements === []) {
                    if ($output->isVerbose()) {
                        $output->writeln('Created <info>guides.project</info> XML root');
                    }
                    $projectElement = $xml->addChild('project');
                } elseif (isset($elements[0])) {
                    $projectElement = $elements[0];
                } else {
                    $output->writeln('Could not access <info>guides.project</info> XML root');
                    continue;
                }
            }

            if ($projectElement instanceof \SimpleXMLElement) {
                if (isset($projectElement[$projectAttribute])) {
                    $output->writeln(sprintf('Updating <info>guides.project[%s]</info> = <info>%s</info>', $projectAttribute, $projectValue));

                    // phpstan reports wrong error: https://github.com/phpstan/phpstan/issues/8236
                    $projectElement[$projectAttribute] = $projectValue;
                } else {
                    $output->writeln(sprintf('Setting <info>guides.project[%s]</info> = <info>%s</info>', $projectAttribute, $projectValue));

                    $projectElement->addAttribute($projectAttribute, $projectValue);
                }
            }
        }

        return true;
    }

    private function operateOnXmlGuides(\SimpleXMLElement $xml, InputInterface $input, OutputInterface $output): bool
    {
        $guidesVariables = [
            'input'                 => $input->getOption('guides-input'),
            'input-file'            => $input->getOption('guides-input-file'),
            'output'                => $input->getOption('guides-output'),
            'input-format'          => $input->getOption('guides-input-format'),
            'log-path'              => $input->getOption('guides-log-path'),
            'fail-on-log'           => $input->getOption('guides-fail-on-log'),
            'show-progress'         => $input->getOption('guides-show-progress'),
            'theme'                 => $input->getOption('guides-theme'),
            'default-code-language' => $input->getOption('guides-default-code-language'),
            'links-are-relative'    => $input->getOption('guides-links-are-relative'),
        ];

        foreach ($guidesVariables as $guideAttribute => $guideValue) {
            if ($guideValue === null) {
                continue;
            }

            if (!is_string($guideValue)) {
                continue;
            }

            $guideAttribute = $this->trimForXml($guideAttribute);
            $guideValue = $this->trimForXml($guideValue);

            if (isset($guides[0]) && $guides[0] instanceof \SimpleXMLElement) {
                if (isset($guides[0][$guideAttribute])) {
                    $output->writeln(sprintf('Updating <info>guides[%s]</info> = <info>%s</info>', $guideAttribute, $guideValue));

                    // phpstan reports wrong error: https://github.com/phpstan/phpstan/issues/8236
                    $guides[0][$guideAttribute] = $guideValue;
                } else {
                    $output->writeln(sprintf('Setting <info>guides[%s]</info> = <info>%s</info>', $guideAttribute, $guideValue));

                    $guides[0]->addAttribute($guideAttribute, $guideValue);
                }
            } else {
                $output->writeln('Could not access <info>guides</info> XML root');
            }
        }

        return true;
    }

    private function operateOnXmlInventory(\SimpleXMLElement $xml, InputInterface $input, OutputInterface $output): bool
    {
        /** @var array<int,string> $inventoryAttributeIds */
        $inventoryAttributeIds  = (array)$input->getOption('inventory-id');
        /** @var array<int,string> $inventoryAttributeUrls */
        $inventoryAttributeUrls = (array)$input->getOption('inventory-url');
        if (count($inventoryAttributeUrls) !== count($inventoryAttributeIds)) {
            $output->writeln('Number of <info>inventory-id</info> and <info>inventory-url</info> arguments must be the same, as they relate to each other.');
        } else {
            $inventoryAttributes = array_combine($inventoryAttributeIds, $inventoryAttributeUrls);

            if ($output->isVerbose()) {
                $output->writeln('List of inventoryAttributes:');
                $output->writeln(print_r($inventoryAttributes, true));
            }

            foreach ($inventoryAttributes as $inventoryId => $inventoryUrl) {
                $inventoryId = $this->trimForXml($inventoryId);
                $inventoryUrl = $this->trimForXml($inventoryUrl);

                // Check if an inventory with the id already exists...
                $elements = $xml->xpath(sprintf('/ns:guides/ns:inventory[@id="%s"]', $inventoryId));
                if ($elements === []) {
                    if ($output->isVerbose()) {
                        $output->writeln('Created <info>guides.inventory</info> XML root');
                    }
                    $inventoryElement = $xml->addChild('inventory');
                } elseif (isset($elements[0])) {
                    $inventoryElement = $elements[0];
                } else {
                    $output->writeln('Could not access <info>guides.inventory</info> XML root');
                    continue;
                }

                // An existing inventoryElement can be removed, if the URL is set empty.
                if (strlen($inventoryUrl) === 0) {
                    $output->writeln(sprintf('Removing empty <info>guides.inventory[id=%s]</info> element.', $inventoryId));
                    unset($inventoryElement[0]);
                } elseif ($inventoryElement instanceof \SimpleXMLElement) {
                    if (isset($inventoryElement['id'])) {
                        $inventoryElement['id'] = $inventoryId;
                    } else {
                        $inventoryElement->addAttribute('id', $inventoryId);
                    }

                    if (isset($inventoryElement['url'])) {
                        $output->writeln(sprintf('Updating <info>guides.inventory[id=%s]</info> = <info>%s</info>', $inventoryId, $inventoryUrl));
                        $inventoryElement['url'] = $inventoryUrl;
                    } else {
                        $output->writeln(sprintf('Setting <info>guides.inventory[id=%s]</info> = <info>%s</info>', $inventoryId, $inventoryUrl));
                        $inventoryElement->addAttribute('url', $inventoryUrl);
                    }
                }
            }
        }

        return true;
    }

    private function operateOnXmlExtension(\SimpleXMLElement $xml, InputInterface $input, OutputInterface $output): bool
    {

        /** @var array<int,string> $extensionAttributeKey */
        $extensionAttributeKey  = (array)$input->getOption('extension-attribute');
        /** @var array<int,string> $extensionAttributeValues */
        $extensionAttributeValues = (array)$input->getOption('extension-value');
        /** @var array<int,string> $extensionAttributeClasses */
        $extensionAttributeClasses = (array)$input->getOption('extension-class');

        if (count($extensionAttributeKey) != count($extensionAttributeValues) || count($extensionAttributeValues) != count($extensionAttributeClasses)) {
            $output->writeln('Number of <info>extension-class</info>, <info>extension-attribute</info> and <info>extension-value</info> arguments must be the same, as they relate to each other.');
        } else {
            $extensionAttributes = array_combine($extensionAttributeKey, $extensionAttributeValues);

            if ($output->isVerbose()) {
                $output->writeln('List of extensionAttributes:');
                $output->writeln(print_r($extensionAttributes, true));
                $output->writeln(print_r($extensionAttributeClasses, true));
            }

            $classIndex = 0;
            foreach ($extensionAttributes as $extensionAttribute => $extensionAttributeValue) {
                $extensionAttribute = $this->trimForXml($extensionAttribute);
                $extensionAttributeValue = $this->trimForXml($extensionAttributeValue);
                $extensionAttributeClasses[$classIndex] = $this->trimForXml($extensionAttributeClasses[$classIndex]);

                // Check if an extension with the id already exists...
                $elements = $xml->xpath(sprintf('/ns:guides/ns:extension[@class="%s"]', $extensionAttributeClasses[$classIndex]));
                if ($elements === []) {
                    if ($output->isVerbose()) {
                        $output->writeln(sprintf('Created <info>guides.extension</info> XML root [class=%s]', $extensionAttributeClasses[$classIndex]));
                    }
                    $extensionElement = $xml->addChild('extension');
                    // phpstan reports wrong error: https://github.com/phpstan/phpstan/issues/8236
                    $extensionElement['class'] = $extensionAttributeClasses[$classIndex];
                } elseif (isset($elements[0])) {
                    $extensionElement = $elements[0];
                } else {
                    $output->writeln('Could not access <info>guides.extension</info> XML root');
                    continue;
                }

                // An existing extensionElement can be removed, if the URL is set empty.
                if (strlen($extensionAttributeValue) === 0 && isset($extensionElement[0][$extensionAttribute])) {
                    $output->writeln(sprintf('Removing empty <info>guides.extension[class=%s, attribute=%s]</info> element.', $extensionAttributeClasses[$classIndex], $extensionAttribute));
                    unset($extensionElement[0][$extensionAttribute]);
                } elseif ($extensionElement instanceof \SimpleXMLElement) {
                    if (isset($extensionElement[$extensionAttribute])) {
                        $output->writeln(sprintf('Updating <info>guides.extension[class=%s, attribute=%s]</info> = <info>%s</info>', $extensionAttributeClasses[$classIndex], $extensionAttribute, $extensionAttributeValue));
                        // phpstan reports wrong error: https://github.com/phpstan/phpstan/issues/8236
                        $extensionElement[$extensionAttribute] = $extensionAttributeValue;
                    } else {
                        $output->writeln(sprintf('Setting <info>guides.extension[class=%s, attribute=%s]</info> = <info>%s</info>', $extensionAttributeClasses[$classIndex], $extensionAttribute, $extensionAttributeValue));
                        $extensionElement->addAttribute($extensionAttribute, $extensionAttributeValue);
                    }
                }
                $classIndex++;
            }
        }

        return true;
    }

    private function operateOnXmlOutputFormat(\SimpleXMLElement $xml, InputInterface $input, OutputInterface $output): bool
    {
        /** @var array<int,string> $outputFormats */
        $outputFormats  = (array)$input->getOption('output-format');

        if ($output->isVerbose()) {
            $output->writeln('List of outputFormats:');
            $output->writeln(print_r($outputFormats, true));
        }

        foreach ($outputFormats as $outputFormat) {
            $outputFormat = $this->trimForXml($outputFormat);
            $elements = $xml->xpath(sprintf('/ns:guides/ns:output-format[text()="%s"]', $outputFormat));
            if ($elements === []) {
                if ($output->isVerbose()) {
                    $output->writeln('Created <info>guides.output-format</info> XML root');
                }
                $outputFormatElement = $xml->addChild('output-format');
            } elseif (isset($elements[0])) {
                $outputFormatElement = $elements[0];
            } else {
                $output->writeln('Could not access <info>guides.output-format</info> XML root');
                continue;
            }

            // An existing inventoryElement can be removed, if the URL is set empty.
            if (strlen($outputFormat) === 0) {
                $output->writeln(sprintf('Removing empty <info>guides.output-format[%s]</info> element.', $outputFormat));
                unset($outputFormatElement[0]);
            } elseif ($outputFormatElement instanceof \SimpleXMLElement) {
                $outputFormatElement[0] = $outputFormat;
                $output->writeln(sprintf('Setting <info>guides.output-format</info> = <info>%s</info>', $outputFormat));
            }
        }

        return true;
    }

    private function operateOnXml(string $config, InputInterface $input, OutputInterface $output): bool
    {
        $xml = simplexml_load_file($config);
        if ($xml === false) {
            $output->writeln(sprintf('<error>Could not parse %s as XML</error>', $config));
            return false;
        }

        // Register the namespace
        $xml->registerXPathNamespace('ns', 'https://www.phpdoc.org/guides');

        $guides = $xml->xpath('/ns:guides');
        if ($guides === []) {
            $output->writeln('<error>Malformed file, missing root "guides" XML element.');
            return false;
        }

        $this->operateOnXmlProject($xml, $input, $output);
        $this->operateOnXmlGuides($xml, $input, $output);
        $this->operateOnXmlInventory($xml, $input, $output);
        $this->operateOnXmlExtension($xml, $input, $output);
        $this->operateOnXmlOutputFormat($xml, $input, $output);

        $xml->asXML($config);

        if ($output->isVeryVerbose()) {
            $output->writeln((string)file_get_contents($config));
        }

        return true;
    }

    private function createEmptyGuides(string $config, OutputInterface $output): bool
    {
        if (!is_dir(dirname($config))) {
            $output->writeln(sprintf('<error>Cannot create guides.xml in missing directory "%s"</error>', dirname($config)));
            return false;
        }

        $fp = fopen($config, 'w');
        if (!$fp) {
            $output->writeln(sprintf('<error>Could not create %s</error>', $config));
            return false;
        }

        $this->xmlDocument = new \DOMDocument('1.0', 'UTF-8');
        $this->xmlDocument->preserveWhiteSpace = true;
        $this->xmlDocument->formatOutput = true;

        $guides = $this->xmlDocument->createElement('guides');

        // Defaults
        $guides->setAttribute('xmlns', 'https://www.phpdoc.org/guides');
        $guides->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $guides->setAttribute('xsi:schemaLocation', 'https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd');
        $guides->setAttribute('links-are-relative', 'true');

        $this->xmlDocument->appendChild($guides);

        fwrite($fp, (string)$this->xmlDocument->saveXML());

        if ($output->isVerbose()) {
            $output->writeln(sprintf('<info>%s</info> created.', $config));
        }

        return true;
    }

    private function trimForXml(string $string): string
    {
        return trim($string, '"\'');
    }
}
