<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5\Rector\New_;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use Rector\Rector\AbstractRector;

/**
 * In Phalcon 4, Phalcon\Cache\AdapterFactory::__construct() accepted an
 * optional SerializerFactory: `__construct(SerializerFactory $factory = null, array $services = [])`.
 * In Phalcon 5 the SerializerFactory argument is required:
 * `__construct(SerializerFactory $serializerFactory, array $services = [])`.
 *
 * Note: the adapter name / options were NEVER constructor arguments in any
 * version — they belong to `newInstance(string $name, array $options)`,
 * which is unchanged between v4 and v5. Standard v4 code like
 * `new AdapterFactory($serializerFactory)` + `$factory->newInstance('apcu', $options)`
 * keeps working in v5 without modification.
 *
 * This rule handles the two broken cases:
 *  1. `new AdapterFactory()` → injects `new SerializerFactory()`
 *  2. `new AdapterFactory(null)` → replaces `null` with `new SerializerFactory()`
 *
 * Any other first argument (an existing SerializerFactory, a variable, etc.)
 * is left untouched.
 *
 * @see https://github.com/phalcon/orphanage-docs/blob/main/en/upgrade.md
 */
final class CacheAdapterFactoryConstructorInjectionRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [New_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof New_) {
            return null;
        }

        $className = $this->getName($node->class);
        if ($className !== 'Phalcon\Cache\AdapterFactory') {
            return null;
        }

        $firstArg = $node->args[0] ?? null;

        // case 1: no argument at all → append `new SerializerFactory()`
        if ($firstArg === null) {
            $node->args[] = new Arg(new New_(new FullyQualified('Phalcon\Storage\SerializerFactory')));

            return $node;
        }

        // already provided a SerializerFactory (or anything unknown) → skip
        if ($firstArg->value instanceof New_ && $this->getName($firstArg->value->class) === 'Phalcon\Storage\SerializerFactory') {
            return null;
        }

        // case 2: explicit `null` first argument → replace with `new SerializerFactory()`
        if ($firstArg->value instanceof ConstFetch && $this->getName($firstArg->value->name) === 'null') {
            $node->args[0] = new Arg(new New_(new FullyQualified('Phalcon\Storage\SerializerFactory')));

            return $node;
        }

        return null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Provide the now-required Phalcon\Storage\SerializerFactory argument of Phalcon\Cache\AdapterFactory constructor (was optional with null default in Phalcon 4)',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$adapterFactory = new Phalcon\Cache\AdapterFactory();
$anotherFactory = new Phalcon\Cache\AdapterFactory(null);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$adapterFactory = new Phalcon\Cache\AdapterFactory(new Phalcon\Storage\SerializerFactory());
$anotherFactory = new Phalcon\Cache\AdapterFactory(new Phalcon\Storage\SerializerFactory());
CODE_SAMPLE
                ),
            ]
        );
    }
}
