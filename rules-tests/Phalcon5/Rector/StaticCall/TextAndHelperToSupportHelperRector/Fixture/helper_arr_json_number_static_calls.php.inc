<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\StaticCall\TextAndHelperToSupportHelperRector\Fixture;

final class ArrAndOthers
{
    public function run(): void
    {
        $first = \Phalcon\Helper\Arr::first($items);
        $firstKey = \Phalcon\Helper\Arr::firstKey($items);
        $last = \Phalcon\Helper\Arr::last($items);
        $value = \Phalcon\Helper\Arr::get($items, 'id', 0);
        $whitelisted = \Phalcon\Helper\Arr::whiteList($items, ['id', 'name']);
        $grouped = \Phalcon\Helper\Arr::group($items, fn ($row) => $row['type']);
        $isUnique = \Phalcon\Helper\Arr::isUnique($items);
        $sliced = \Phalcon\Helper\Arr::sliceLeft($items, 2);

        $json = \Phalcon\Helper\Json::encode($data, 0, 512);
        $decoded = \Phalcon\Helper\Json::decode($json, true);

        $inRange = \Phalcon\Helper\Number::between(5, 1, 10);
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\StaticCall\TextAndHelperToSupportHelperRector\Fixture;

final class ArrAndOthers
{
    public function run(): void
    {
        $first = (new \Phalcon\Support\HelperFactory())->first($items);
        $firstKey = (new \Phalcon\Support\HelperFactory())->firstKey($items);
        $last = (new \Phalcon\Support\HelperFactory())->last($items);
        $value = (new \Phalcon\Support\HelperFactory())->get($items, 'id', 0);
        $whitelisted = (new \Phalcon\Support\HelperFactory())->whitelist($items, ['id', 'name']);
        $grouped = (new \Phalcon\Support\HelperFactory())->group($items, fn ($row) => $row['type']);
        $isUnique = (new \Phalcon\Support\HelperFactory())->isUnique($items);
        $sliced = (new \Phalcon\Support\HelperFactory())->sliceLeft($items, 2);

        $json = (new \Phalcon\Support\HelperFactory())->encode($data, 0, 512);
        $decoded = (new \Phalcon\Support\HelperFactory())->decode($json, true);

        $inRange = (new \Phalcon\Support\HelperFactory())->isBetween(5, 1, 10);
    }
}
