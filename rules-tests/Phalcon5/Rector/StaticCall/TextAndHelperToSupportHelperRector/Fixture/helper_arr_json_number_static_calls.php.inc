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

use Phalcon\Support\HelperFactory;

final class ArrAndOthers
{
    public function run(): void
    {
        $first = (new HelperFactory())->first($items);
        $firstKey = (new HelperFactory())->firstKey($items);
        $last = (new HelperFactory())->last($items);
        $value = (new HelperFactory())->get($items, 'id', 0);
        $whitelisted = (new HelperFactory())->whitelist($items, ['id', 'name']);
        $grouped = (new HelperFactory())->group($items, fn ($row) => $row['type']);
        $isUnique = (new HelperFactory())->isUnique($items);
        $sliced = (new HelperFactory())->sliceLeft($items, 2);

        $json = (new HelperFactory())->encode($data, 0, 512);
        $decoded = (new HelperFactory())->decode($json, true);

        $inRange = (new HelperFactory())->isBetween(5, 1, 10);
    }
}
