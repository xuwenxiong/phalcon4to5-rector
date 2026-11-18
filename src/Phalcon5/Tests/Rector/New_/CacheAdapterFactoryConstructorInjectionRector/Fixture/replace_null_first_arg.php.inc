<?php

namespace RectorPhalcon\Phalcon5\Tests\Rector\New_\CacheAdapterFactoryConstructorInjectionRector\Fixture;

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

namespace RectorPhalcon\Phalcon5\Tests\Rector\New_\CacheAdapterFactoryConstructorInjectionRector\Fixture;

use Phalcon\Storage\SerializerFactory;

final class SomeClass
{
    public function run(): void
    {
        // legal in Phalcon 4 (SerializerFactory was optional, null default),
        // fatal in Phalcon 5 (required argument)
        $adapterFactory = new \Phalcon\Cache\AdapterFactory(new SerializerFactory());
        $anotherFactory = new \Phalcon\Cache\AdapterFactory(new SerializerFactory(), []);
    }
}
