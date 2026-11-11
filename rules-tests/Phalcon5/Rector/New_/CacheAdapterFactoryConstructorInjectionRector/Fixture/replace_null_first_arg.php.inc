<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\New_\CacheAdapterFactoryConstructorInjectionRector\Fixture;

final class SomeClass
{
    public function run(): void
    {
        // legal in Phalcon 4 (SerializerFactory was optional, null default),
        // fatal in Phalcon 5 (required argument)
        $adapterFactory = new \Phalcon\Cache\AdapterFactory(null);
        $anotherFactory = new \Phalcon\Cache\AdapterFactory(null, []);
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\New_\CacheAdapterFactoryConstructorInjectionRector\Fixture;

final class SomeClass
{
    public function run(): void
    {
        // legal in Phalcon 4 (SerializerFactory was optional, null default),
        // fatal in Phalcon 5 (required argument)
        $adapterFactory = new \Phalcon\Cache\AdapterFactory(new \Phalcon\Storage\SerializerFactory());
        $anotherFactory = new \Phalcon\Cache\AdapterFactory(new \Phalcon\Storage\SerializerFactory(), []);
    }
}
