<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\PhpDomain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FullyQualifiedNameServiceTest extends TestCase
{
    private FullyQualifiedNameService $fullyQualifiedNameService;
    protected function setUp(): void
    {
        $this->fullyQualifiedNameService = new FullyQualifiedNameService();
    }
    #[DataProvider('validFqnProvider')]
    public function testValidClassNames(string $expectedName, string|null $expectedNamespace, string $fqn): void
    {
        $result = $this->fullyQualifiedNameService->getFullyQualifiedName($fqn);
        self::assertEquals($expectedName, $result->getName());
        self::assertEquals($expectedNamespace, $result->getNamespaceNode()?->getName());
    }
    /**
     * @return array<int, mixed>
     */
    public static function validFqnProvider(): array
    {
        return [
            // Valid FQN with both namespace and class name
            ['YourClassName', 'Your\\Namespace\\', 'Your\\Namespace\\YourClassName'],

            // Valid FQN with only class name
            ['AnotherClassName', null, 'AnotherClassName'],
        ];
    }
    #[DataProvider('inValidFqnProvider')]
    public function testInValidClassNames(string $fqn): void
    {
        $this->expectException(\Exception::class);
        $result = $this->fullyQualifiedNameService->getFullyQualifiedName($fqn);
    }
    /**
     * @return array<int, mixed>
     */
    public static function inValidFqnProvider(): array
    {
        return [
            // Valid FQN with both namespace and class name
            ['Your/Namespace/YourClassName'],

            // Valid FQN with only class name
            [' AnotherClassName'],
        ];
    }
}
