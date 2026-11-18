<?php

declare(strict_types=1);

namespace RectorPhalcon\Support\ValueObject;

enum PhalconVersion: int
{
    case V3 = 3;
    case V4 = 4;
    case V5 = 5;
    case V6 = 6;
}
