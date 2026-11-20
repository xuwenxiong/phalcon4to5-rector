<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\RegisterEnginesClosureRector\Fixture;

final class TwoArgs
{
    public function run($view, $di, $config): void
    {
        $view->registerEngines([
            '.phtml' => function ($view, $di) use ($config) {
                $volt = new \Marser\App\Core\PhalBaseVolt($view, $di);
                $volt->setOptions(['compiledPath' => $config->path]);
                return $volt;
            },
        ]);
    }
}
-----
<?php

namespace RectorPhalcon\Phalcon4\Tests\Rector\MethodCall\RegisterEnginesClosureRector\Fixture;

final class TwoArgs
{
    public function run($view, $di, $config): void
    {
        $view->registerEngines([
            '.phtml' => function ($view) use ($config) {
                $volt = new \Marser\App\Core\PhalBaseVolt($view, $this);
                $volt->setOptions(['compiledPath' => $config->path]);
                return $volt;
            },
        ]);
    }
}
