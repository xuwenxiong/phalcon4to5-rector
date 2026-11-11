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
        $first = (new \Phalcon\Support\Helper\Arr\First())->__invoke($items);
        $firstKey = (new \Phalcon\Support\Helper\Arr\FirstKey())->__invoke($items);
        $last = (new \Phalcon\Support\Helper\Arr\Last())->__invoke($items);
        $value = (new \Phalcon\Support\Helper\Arr\Get())->__invoke($items, 'id', 0);
        $whitelisted = (new \Phalcon\Support\Helper\Arr\Whitelist())->__invoke($items, ['id', 'name']);
        $grouped = (new \Phalcon\Support\Helper\Arr\Group())->__invoke($items, fn ($row) => $row['type']);
        $isUnique = (new \Phalcon\Support\Helper\Arr\IsUnique())->__invoke($items);
        $sliced = (new \Phalcon\Support\Helper\Arr\SliceLeft())->__invoke($items, 2);

        $json = (new \Phalcon\Support\Helper\Json\Encode())->__invoke($data, 0, 512);
        $decoded = (new \Phalcon\Support\Helper\Json\Decode())->__invoke($json, true);

        $inRange = (new \Phalcon\Support\Helper\Number\IsBetween())->__invoke(5, 1, 10);
    }
}
