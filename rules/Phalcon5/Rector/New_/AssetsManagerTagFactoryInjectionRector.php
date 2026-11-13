<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5\Rector\New_;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\New_;
use PHPStan\Type\ObjectType;
use Phalcon4To5\Rector\Phalcon5\PhalconImportShortener;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use Rector\Rector\AbstractRector;

/**
 * In Phalcon 4, Phalcon\Assets\Manager::__construct() accepted only optional options:
 * `__construct(array $options = [])`.
 * In Phalcon 5 a TagFactory is required as first argument:
 * `__construct(TagFactory $tagFactory, array $options = [])`.
 *
 * TagFactory itself requires an escaper: `TagFactory::__construct(EscaperInterface $escaper, ...)`.
 * This rule injects the full chain: `new TagFactory(new Escaper())`.
 *
 *  - no arguments            → prepend `new TagFactory(new Escaper())`
 *  - first arg is options    → prepend `new TagFactory(new Escaper())` before it
 *  - first arg is TagFactory → skip
 *
 * @see https://github.com/phalcon/orphanage-docs/blob/main/en/upgrade.md
 */
final class AssetsManagerTagFactoryInjectionRector extends AbstractRector
{
    public function __construct(
        private PhalconImportShortener $phalconImportShortener
    ) {
    }

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
        if ($className !== 'Phalcon\Assets\Manager') {
            return null;
        }

        $firstArg = $node->args[0] ?? null;

        // already has a TagFactory (inline `new` or a typed variable) → skip
        if ($firstArg !== null) {
            $isTagFactory = $firstArg->value instanceof New_
                && $this->getName($firstArg->value->class) === 'Phalcon\Html\TagFactory';

            if (! $isTagFactory) {
                $isTagFactory = $this->nodeTypeResolver->isObjectType(
                    $firstArg->value,
                    new ObjectType('Phalcon\Html\TagFactory')
                );
            }

            if ($isTagFactory) {
                return null;
            }
        }

        $file = $this->getFile();

        $tagFactory = new Arg(
            new New_(
                $this->phalconImportShortener->shortenCreatedClass('Phalcon\Html\TagFactory', $file),
                [new Arg(new New_($this->phalconImportShortener->shortenCreatedClass('Phalcon\Html\Escaper', $file)))]
            )
        );

        // no arguments → append the factory
        if ($firstArg === null) {
            $node->args[] = $tagFactory;

            return $node;
        }

        // first argument is the v4 options array → prepend the factory
        array_unshift($node->args, $tagFactory);

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Inject the now-required Phalcon\Html\TagFactory (with its Escaper dependency) into Phalcon\Assets\Manager constructor (required in Phalcon 5)',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$assets = new Phalcon\Assets\Manager();
$assetsWithOptions = new Phalcon\Assets\Manager(['sourceBasePath' => '/src']);
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$assets = new Phalcon\Assets\Manager(new TagFactory(new Escaper()));
$assetsWithOptions = new Phalcon\Assets\Manager(new TagFactory(new Escaper()), ['sourceBasePath' => '/src']);
CODE_SAMPLE
                ),
            ]
        );
    }
}
