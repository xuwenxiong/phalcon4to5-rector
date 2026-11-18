<?php

namespace RectorPhalcon\Phalcon5\Tests\Rector\New_\CacheAdapterFactoryConstructorInjectionRector\Fixture;

final class SomeClass
{
    public function run(): void
    {
        $adapterFactory = new \Phalcon\Cache\AdapterFactory();
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
        $adapterFactory = new \Phalcon\Cache\AdapterFactory(new SerializerFactory());
    }
}
