<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5\Rector\StaticCall;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use Rector\Rector\AbstractRector;

/**
 * In Phalcon 5, the `Phalcon\Text` and `Phalcon\Helper\*` components have been
 * removed. Their static helpers were moved to instance classes under
 * `Phalcon\Support\Helper\*`, which are no longer static — each helper is a
 * class with an `__invoke()` method and no constructor arguments.
 *
 * This rule rewrites static calls into instance calls:
 *
 *      Phalcon\Text::camelize('foo_bar')                → (new Phalcon\Support\Helper\Str\Camelize())->__invoke('foo_bar')
 *      Phalcon\Helper\Str::endsWith('a', 'b')           → (new Phalcon\Support\Helper\Str\EndsWith())->__invoke('a', 'b')
 *      Phalcon\Helper\Arr::whiteList($arr, ['id'])      → (new Phalcon\Support\Helper\Arr\Whitelist())->__invoke($arr, ['id'])
 *      Phalcon\Text::RANDOM_ALPHA                       → Phalcon\Support\Helper\Str\Random::RANDOM_ALPHA
 *
 * Method names are matched case-insensitively (v4 used camelCase, the v5 class
 * names are PascalCase).
 *
 * Unmapped calls (e.g. `Phalcon\Helper\File`, which never existed in v4) are
 * left untouched.
 *
 * @see https://github.com/phalcon/orphanage-docs/blob/main/en/upgrade.md
 */
final class TextAndHelperToSupportHelperRector extends AbstractRector
{
    /**
     * [sourceClass => [sourceMethod (lowercase) => targetClass]]
     */
    private const METHOD_MAP = [
        'phalcon\text' => [
            'camelize' => 'Phalcon\Support\Helper\Str\Camelize',
            'concat' => 'Phalcon\Support\Helper\Str\Concat',
            'dynamic' => 'Phalcon\Support\Helper\Str\Dynamic',
            'endswith' => 'Phalcon\Support\Helper\Str\EndsWith',
            'humanize' => 'Phalcon\Support\Helper\Str\Humanize',
            'increment' => 'Phalcon\Support\Helper\Str\Increment',
            'lower' => 'Phalcon\Support\Helper\Str\Lower',
            'random' => 'Phalcon\Support\Helper\Str\Random',
            'reduceslashes' => 'Phalcon\Support\Helper\Str\ReduceSlashes',
            'startswith' => 'Phalcon\Support\Helper\Str\StartsWith',
            'uncamelize' => 'Phalcon\Support\Helper\Str\Uncamelize',
            'underscore' => 'Phalcon\Support\Helper\Str\Underscore',
            'upper' => 'Phalcon\Support\Helper\Str\Upper',
        ],
        'phalcon\helper\str' => [
            'camelize' => 'Phalcon\Support\Helper\Str\Camelize',
            'concat' => 'Phalcon\Support\Helper\Str\Concat',
            'countvowels' => 'Phalcon\Support\Helper\Str\CountVowels',
            'decapitalize' => 'Phalcon\Support\Helper\Str\Decapitalize',
            'decrement' => 'Phalcon\Support\Helper\Str\Decrement',
            'dirfromfile' => 'Phalcon\Support\Helper\Str\DirFromFile',
            'dirseparator' => 'Phalcon\Support\Helper\Str\DirSeparator',
            'dynamic' => 'Phalcon\Support\Helper\Str\Dynamic',
            'endswith' => 'Phalcon\Support\Helper\Str\EndsWith',
            'firstbetween' => 'Phalcon\Support\Helper\Str\FirstBetween',
            'humanize' => 'Phalcon\Support\Helper\Str\Humanize',
            'includes' => 'Phalcon\Support\Helper\Str\Includes',
            'increment' => 'Phalcon\Support\Helper\Str\Increment',
            'isanagram' => 'Phalcon\Support\Helper\Str\IsAnagram',
            'islower' => 'Phalcon\Support\Helper\Str\IsLower',
            'ispalindrome' => 'Phalcon\Support\Helper\Str\IsPalindrome',
            'isupper' => 'Phalcon\Support\Helper\Str\IsUpper',
            'lower' => 'Phalcon\Support\Helper\Str\Lower',
            'random' => 'Phalcon\Support\Helper\Str\Random',
            'reduceslashes' => 'Phalcon\Support\Helper\Str\ReduceSlashes',
            'startswith' => 'Phalcon\Support\Helper\Str\StartsWith',
            'uncamelize' => 'Phalcon\Support\Helper\Str\Uncamelize',
            'underscore' => 'Phalcon\Support\Helper\Str\Underscore',
            'upper' => 'Phalcon\Support\Helper\Str\Upper',
        ],
        'phalcon\helper\arr' => [
            'chunk' => 'Phalcon\Support\Helper\Arr\Chunk',
            'filter' => 'Phalcon\Support\Helper\Arr\Filter',
            'first' => 'Phalcon\Support\Helper\Arr\First',
            'firstkey' => 'Phalcon\Support\Helper\Arr\FirstKey',
            'flatten' => 'Phalcon\Support\Helper\Arr\Flatten',
            'get' => 'Phalcon\Support\Helper\Arr\Get',
            'group' => 'Phalcon\Support\Helper\Arr\Group',
            'has' => 'Phalcon\Support\Helper\Arr\Has',
            'isunique' => 'Phalcon\Support\Helper\Arr\IsUnique',
            'last' => 'Phalcon\Support\Helper\Arr\Last',
            'lastkey' => 'Phalcon\Support\Helper\Arr\LastKey',
            'order' => 'Phalcon\Support\Helper\Arr\Order',
            'pluck' => 'Phalcon\Support\Helper\Arr\Pluck',
            'set' => 'Phalcon\Support\Helper\Arr\Set',
            'sliceleft' => 'Phalcon\Support\Helper\Arr\SliceLeft',
            'sliceright' => 'Phalcon\Support\Helper\Arr\SliceRight',
            'split' => 'Phalcon\Support\Helper\Arr\Split',
            'toobject' => 'Phalcon\Support\Helper\Arr\ToObject',
            'validateall' => 'Phalcon\Support\Helper\Arr\ValidateAll',
            'validateany' => 'Phalcon\Support\Helper\Arr\ValidateAny',
            'whitelist' => 'Phalcon\Support\Helper\Arr\Whitelist',
        ],
        'phalcon\helper\json' => [
            'decode' => 'Phalcon\Support\Helper\Json\Decode',
            'encode' => 'Phalcon\Support\Helper\Json\Encode',
        ],
        'phalcon\helper\number' => [
            // v4 method was `between`, v5 class is IsBetween
            'between' => 'Phalcon\Support\Helper\Number\IsBetween',
        ],
    ];

    private const TEXT_CLASS = 'phalcon\text';

    /**
     * RANDOM_* constants moved from Phalcon\Text to Phalcon\Support\Helper\Str\Random
     */
    private const RANDOM_CONSTANTS = [
        'RANDOM_ALNUM', 'RANDOM_ALPHA', 'RANDOM_DISTINCT', 'RANDOM_HEXDEC', 'RANDOM_NOZERO', 'RANDOM_NUMERIC',
    ];

    public function getNodeTypes(): array
    {
        return [StaticCall::class, ClassConstFetch::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof StaticCall) {
            return $this->refactorStaticCall($node);
        }

        if ($node instanceof ClassConstFetch) {
            return $this->refactorClassConstFetch($node);
        }

        return null;
    }

    private function refactorStaticCall(StaticCall $node): ?Node
    {
        $className = $this->getName($node->class);
        if ($className === null) {
            return null;
        }

        // dynamic method call ($class::$method()) → skip
        if (! $node->name instanceof Identifier) {
            return null;
        }

        $map = self::METHOD_MAP[strtolower($className)] ?? null;
        if ($map === null) {
            return null;
        }

        $targetClass = $map[strtolower($node->name->toString())] ?? null;
        if ($targetClass === null) {
            return null;
        }

        // v5 helpers are instance classes: (new Target())->__invoke(...)
        return new MethodCall(
            new New_(new FullyQualified($targetClass)),
            new Identifier('__invoke'),
            $node->args
        );
    }

    private function refactorClassConstFetch(ClassConstFetch $node): ?Node
    {
        $className = $this->getName($node->class);
        if ($className === null || strtolower($className) !== self::TEXT_CLASS) {
            return null;
        }

        if (! $node->name instanceof Identifier) {
            return null;
        }

        $constantName = strtoupper($node->name->toString());
        if (! in_array($constantName, self::RANDOM_CONSTANTS, true)) {
            return null;
        }

        return new ClassConstFetch(
            new FullyQualified('Phalcon\Support\Helper\Str\Random'),
            new Identifier($constantName)
        );
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Migrate removed Phalcon\Text and Phalcon\Helper\* static helpers to Phalcon\Support\Helper\* instance classes (__invoke)',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$camel = Phalcon\Text::camelize('foo_bar');
$endsWith = Phalcon\Helper\Str::endsWith('hello world', 'world');
$first = Phalcon\Helper\Arr::first($items);
$json = Phalcon\Helper\Json::encode($data);
$type = Phalcon\Text::RANDOM_ALPHA;
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$camel = (new Phalcon\Support\Helper\Str\Camelize())->__invoke('foo_bar');
$endsWith = (new Phalcon\Support\Helper\Str\EndsWith())->__invoke('hello world', 'world');
$first = (new Phalcon\Support\Helper\Arr\First())->__invoke($items);
$json = (new Phalcon\Support\Helper\Json\Encode())->__invoke($data);
$type = Phalcon\Support\Helper\Str\Random::RANDOM_ALPHA;
CODE_SAMPLE
                ),
            ]
        );
    }
}
