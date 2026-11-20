<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\RegisterEnginesClosureRector\Fixture;

final class StringEngine
{
    public function run($view): void
    {
        $view->registerEngines([
            '.phtml' => \Phalcon\Mvc\View\Engine\Php::class,
        ]);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\RegisterEnginesClosureRector\Fixture;

final class StringEngine
{
    public function run($view): void
    {
        $view->registerEngines([
            '.phtml' => \Phalcon\Mvc\View\Engine\Php::class,
        ]);
    }
}
