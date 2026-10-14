<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Events\Reflection;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\Metadata\MetaDataContainer;
use phpDocumentor\Reflection\Php\Factory\ContextStack;
use phpDocumentor\Reflection\Php\Factory\Reducer\Reducer;
use phpDocumentor\Reflection\Php\Function_;
use phpDocumentor\Reflection\Php\Method;
use phpDocumentor\Reflection\Php\StrategyContainer;
use phpDocumentor\Reflection\Types\Context;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;

/**
 * Finds the events that a method or function passes to `dispatch()`.
 *
 * A reducer and not a strategy, because a strategy only receives the
 * statements at the top of a method body: a dispatch inside `try`, `foreach`
 * or `return` would be missed. A reducer receives the whole method node.
 *
 * The event is known when it is created in the call, when it is assigned to
 * the variable with `new` in the same method, or when it is a parameter
 * declared with its class. Only class names ending in `Event` count, which
 * keeps out the messages that go to the Symfony message bus.
 *
 * phpDocumentor does not resolve the names inside a method body, so the
 * reducer resolves them with the namespace and the imports of the file.
 */
final class DispatchedEventsReducer implements Reducer
{
    private readonly NodeFinder $finder;

    public function __construct(private readonly FqsenResolver $fqsenResolver)
    {
        $this->finder = new NodeFinder();
    }

    public function reduce(
        ContextStack $context,
        object $object,
        StrategyContainer $strategies,
        object|null $carry,
    ): object|null {
        if (!($carry instanceof Method || $carry instanceof Function_)
            || !$carry instanceof MetaDataContainer
            || !$object instanceof FunctionLike
            || $object->getStmts() === null
        ) {
            return $carry;
        }

        $typeContext = $context->getTypeContext();
        $events = [];
        foreach ($this->findDispatchCalls($object) as $call) {
            foreach ($this->resolveEvents($object, $call->getArgs()[0]->value, $typeContext) as $event) {
                $events[] = $event;
            }
        }

        if ($events === []) {
            return $carry;
        }

        $metadata = $carry->getMetadata()[DispatchedEvents::KEY] ?? new DispatchedEvents();
        if (!$metadata instanceof DispatchedEvents) {
            return $carry;
        }

        foreach ($events as $event) {
            $metadata->add($event);
        }

        $carry->addMetadata($metadata);

        return $carry;
    }

    /** @return list<MethodCall> */
    private function findDispatchCalls(FunctionLike $function): array
    {
        /** @var list<MethodCall> */
        return $this->finder->find(
            $function->getStmts() ?? [],
            static fn(Node $node): bool => $node instanceof MethodCall
                && $node->name instanceof Identifier
                && $node->name->toLowerString() === 'dispatch'
                && count($node->getArgs()) === 1,
        );
    }

    /** @return list<Fqsen> */
    private function resolveEvents(FunctionLike $function, Node\Expr $argument, ?Context $typeContext): array
    {
        if ($argument instanceof New_) {
            return $this->eventName($argument->class, $typeContext);
        }

        if (!$argument instanceof Variable || !is_string($argument->name)) {
            return [];
        }

        $name = $argument->name;
        $events = [];
        foreach ($function->getParams() as $param) {
            if ($param->var instanceof Variable && $param->var->name === $name) {
                $events = $this->eventName($param->type, $typeContext);
            }
        }

        /** @var list<Assign> $assignments */
        $assignments = $this->finder->find(
            $function->getStmts() ?? [],
            static fn(Node $node): bool => $node instanceof Assign
                && $node->var instanceof Variable
                && $node->var->name === $name
                && $node->expr instanceof New_,
        );
        foreach ($assignments as $assignment) {
            assert($assignment->expr instanceof New_);
            $events = [...$events, ...$this->eventName($assignment->expr->class, $typeContext)];
        }

        return $events;
    }

    /** @return list<Fqsen> */
    private function eventName(?Node $class, ?Context $typeContext): array
    {
        if (!$class instanceof Name || !str_ends_with($class->toString(), 'Event')) {
            return [];
        }

        return [$this->fqsenResolver->resolve($class->toCodeString(), $typeContext)];
    }
}
