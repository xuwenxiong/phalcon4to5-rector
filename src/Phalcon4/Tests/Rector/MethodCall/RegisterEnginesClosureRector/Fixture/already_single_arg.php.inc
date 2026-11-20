<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\RegisterEnginesClosureRector\Fixture;

final class AlreadySingleArg
{
    public function run($view): void
    {
        $view->registerEngines([
            '.phtml' => function ($view) {
                return new \Phalcon\Mvc\View\Engine\Php($view, $this);
            },
        ]);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\RegisterEnginesClosureRector\Fixture;

final class AlreadySingleArg
{
    public function run($view): void
    {
        $view->registerEngines([
            '.phtml' => function ($view) {
                return new \Phalcon\Mvc\View\Engine\Php($view, $this);
            },
        ]);
    }
}
