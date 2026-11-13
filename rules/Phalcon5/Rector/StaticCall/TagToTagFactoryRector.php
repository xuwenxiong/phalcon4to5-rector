<?php

declare(strict_types=1);

namespace Phalcon4To5\Rector\Phalcon5\Rector\StaticCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use Phalcon4To5\Rector\Phalcon5\PhalconImportShortener;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use Rector\Rector\AbstractRector;

/**
 * In Phalcon 5, Phalcon\Tag has been removed. HTML generation moved to
 * Phalcon\Html\Helper\* classes accessed through a Phalcon\Html\TagFactory
 * instance (`$tagFactory->inputText(...)` etc.).
 *
 * This rule rewrites the deterministic subset of Tag static calls:
 *
 *  - input field methods (textField, emailField, checkField, ...):
 *      Tag::textField('name')                        → $tagFactory->inputText('name')
 *      Tag::textField(['name', 'value' => 'v', 'class' => 'c'])
 *                                                    → $tagFactory->inputText('name', 'v', ['class' => 'c'])
 *  - friendlyText(...) → $tagFactory->friendlyTitle(...) (identical signature)
 *  - HTML doctype constants (Tag::HTML5, ...) → Doctype::HTML5, ... (same names)
 *
 * The target is a `$tagFactory` variable, which must exist in scope (a
 * Phalcon\Html\TagFactory service from the DI container). This is an explicit,
 * documented convention.
 *
 * The following are intentionally NOT migrated (non-deterministic parameter
 * semantics, DI/state dependent, or require manual rewrite):
 *  - linkTo(), image(), stylesheetLink(), javascriptInclude(), select(),
 *    selectStatic(), tagHtml(), tagHtmlClose(), renderAttributes()
 *  - stateful methods: setTitle/prependTitle/appendTitle/getTitle*(),
 *    setDefault/setDefaults/displayTo/getValue/hasValue/resetInput(),
 *    setAutoescape(), setDocType/getDocType()
 *  - DI accessors: setDI/getDI/getEscaper()/getEscaperService()/getUrlService()
 *
 * @see https://github.com/phalcon/orphanage-docs/blob/main/en/upgrade.md
 */
final class TagToTagFactoryRector extends AbstractRector
{
    public function __construct(
        private PhalconImportShortener $phalconImportShortener
    ) {
    }

    /**
     * v4 Tag input methods → v5 TagFactory method names.
     * All produce (name, value, attributes) positional arguments.
     */
    private const INPUT_METHOD_MAP = [
        'textfield' => 'inputText',
        'emailfield' => 'inputEmail',
        'datefield' => 'inputDate',
        'datetimefield' => 'inputDateTime',
        'datetimelocalfield' => 'inputDateTimeLocal',
        'filefield' => 'inputFile',
        'hiddenfield' => 'inputHidden',
        'imageinput' => 'inputImage',
        'monthfield' => 'inputMonth',
        'numericfield' => 'inputNumeric',
        'passwordfield' => 'inputPassword',
        'rangefield' => 'inputRange',
        'searchfield' => 'inputSearch',
        'telfield' => 'inputTel',
        'timefield' => 'inputTime',
        'urlfield' => 'inputUrl',
        'weekfield' => 'inputWeek',
        'colorfield' => 'inputColor',
        'textarea' => 'inputTextarea',
        'submitbutton' => 'inputSubmit',
        'checkfield' => 'inputCheckbox',
        'radiofield' => 'inputRadio',
    ];

    private const TAG_CLASS = 'phalcon\tag';

    private const TARGET_VARIABLE = 'tagFactory';

    /**
     * Phalcon\Tag doctype constants → Phalcon\Html\Helper\Doctype constants (same names)
     */
    private const DOCTYPE_CONSTANTS = [
        'HTML32',
        'HTML401_STRICT',
        'HTML401_TRANSITIONAL',
        'HTML401_FRAMESET',
        'HTML5',
        'XHTML10_STRICT',
        'XHTML10_TRANSITIONAL',
        'XHTML10_FRAMESET',
        'XHTML11',
        'XHTML20',
        'XHTML5',
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
        if ($className === null || strtolower($className) !== self::TAG_CLASS) {
            return null;
        }

        if (! $node->name instanceof Identifier) {
            return null;
        }

        $methodName = strtolower($node->name->toString());

        // friendlyText → friendlyTitle (identical signature, args pass through)
        if ($methodName === 'friendlytext') {
            return new MethodCall(
                new Variable(self::TARGET_VARIABLE),
                new Identifier('friendlyTitle'),
                $node->args
            );
        }

        $factoryMethod = self::INPUT_METHOD_MAP[$methodName] ?? null;
        if ($factoryMethod === null) {
            return null;
        }

        $arguments = $this->resolveInputArguments($node);
        if ($arguments === null) {
            return null;
        }

        return new MethodCall(
            new Variable(self::TARGET_VARIABLE),
            new Identifier($factoryMethod),
            $arguments
        );
    }

    private function refactorClassConstFetch(ClassConstFetch $node): ?Node
    {
        $className = $this->getName($node->class);
        if ($className === null || strtolower($className) !== self::TAG_CLASS) {
            return null;
        }

        if (! $node->name instanceof Identifier) {
            return null;
        }

        $constantName = strtoupper($node->name->toString());
        if (! in_array($constantName, self::DOCTYPE_CONSTANTS, true)) {
            return null;
        }

        // queue the `use Phalcon\Html\Helper\Doctype;` import: nodes created
        // here are never revisited by RenameUseImportsRector
        return new ClassConstFetch(
            $this->phalconImportShortener->shortenCreatedClass('Phalcon\Html\Helper\Doctype', $this->getFile()),
            new Identifier($constantName)
        );
    }

    /**
     * Builds the (name, value, attributes) argument list for input helpers.
     * Only deterministic forms are supported:
     *  - a single string argument (the field name)
     *  - a literal array: first positional item = name, 'value' key = value,
     *    remaining named keys = attributes
     * Anything else (variables, function calls, extra positional items) → null (skip).
     *
     * @return array<Arg>|null
     */
    private function resolveInputArguments(StaticCall $node): ?array
    {
        if ($node->args === []) {
            return null;
        }

        $firstArgValue = $node->args[0]->value;

        // string form: Tag::textField('name') → inputText('name')
        if ($firstArgValue instanceof String_) {
            return [$node->args[0]];
        }

        // literal array form: ['name', 'value' => 'x', 'class' => 'c']
        if ($firstArgValue instanceof Array_) {
            $name = null;
            $value = null;
            $attributes = [];

            foreach ($firstArgValue->items as $item) {
                if (! $item instanceof ArrayItem) {
                    return null;
                }

                // named key
                if ($item->key !== null && $item->key instanceof String_) {
                    $key = strtolower($item->key->value);
                    if ($key === 'value') {
                        $value = $item->value;
                        continue;
                    }

                    $attributes[] = new ArrayItem($item->value, $item->key);
                    continue;
                }

                // first positional item = field name; extra positional items
                // have no v5 equivalent → skip the whole call
                if ($name === null) {
                    $name = $item->value;
                    continue;
                }

                return null;
            }

            if ($name === null) {
                return null;
            }

            $arguments = [new Arg($name)];
            if ($value !== null) {
                $arguments[] = new Arg($value);
            } elseif ($attributes !== []) {
                // v5 signature is (name, value, attributes): keep positional
                // alignment when only attributes are present
                $arguments[] = new Arg($this->nodeFactory->createNull());
            }

            if ($attributes !== []) {
                $arguments[] = new Arg(new Array_($attributes));
            }

            return $arguments;
        }

        // non-deterministic form (variable, function call, ...) → skip
        return null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Migrate removed Phalcon\Tag static calls (deterministic subset) to Phalcon\Html\TagFactory method calls on a $tagFactory variable',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$name = Phalcon\Tag::textField(['name', 'value' => 'x', 'class' => 'c']);
$slug = Phalcon\Tag::friendlyText('Some Title', '-');
$doctype = Phalcon\Tag::HTML5;
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$name = $tagFactory->inputText('name', 'x', ['class' => 'c']);
$slug = $tagFactory->friendlyTitle('Some Title', '-');
$doctype = Doctype::HTML5;
CODE_SAMPLE
                ),
            ]
        );
    }
}
