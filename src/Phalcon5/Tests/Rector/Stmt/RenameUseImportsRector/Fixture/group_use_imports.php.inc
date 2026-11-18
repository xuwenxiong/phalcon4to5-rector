<?php

use Phalcon\{Url as UrlResolver, Di, Loader};

final class GroupUseService
{
    public function build(): string
    {
        $loader = new Loader();
        $di = Di::getDefault();

        return (new UrlResolver())->get('/a.gif') . get_class($loader) . get_class($di);
    }
}
-----
<?php

use Phalcon\{Mvc\Url as UrlResolver, Di\Di, Autoload\Loader};

final class GroupUseService
{
    public function build(): string
    {
        $loader = new Loader();
        $di = Di::getDefault();

        return (new UrlResolver())->get('/a.gif') . get_class($loader) . get_class($di);
    }
}
