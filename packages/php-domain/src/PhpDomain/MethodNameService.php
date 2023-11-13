<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\PhpDomain;

use Psr\Log\LoggerInterface;
use T3Docs\PhpDomain\Nodes\MethodNameNode;

class MethodNameService
{
    /** https://regex101.com/r/QrrSXk/1 */
    private const METHOD_SIGNATURE_REGEX = '/^\s*(\w+)\s*\(\s*(.*?)\s*\)\s*(?::\s*(\w+))?\s*$/';

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function getMethodName(string $name): MethodNameNode
    {
        if (preg_match(self::METHOD_SIGNATURE_REGEX, $name, $matches)) {
            $methodName = $matches[1];
            $parameters = isset($matches[2]) ? $matches[2] : '';
            $returnType = $matches[3] ?? null;
            $parametersArray = preg_split('/\s*,\s*/', $parameters, -1, PREG_SPLIT_NO_EMPTY);
            if ($parametersArray === false) {
                $parametersArray = [];
            }
            return new MethodNameNode($methodName, $parametersArray, $returnType);
        }
        $this->logger->warning(sprintf('Method signature %s is invalid. ', $name));
        return new MethodNameNode($name, [], null);
    }
}
