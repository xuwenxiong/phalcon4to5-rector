<?php

declare(strict_types=1);

namespace RectorPhalcon\Phalcon4;

final class DispatcherConstants
{
    /**
     * Dispatcher exception constants moved from Phalcon\Dispatcher (v3) to
     * Phalcon\Dispatcher\Exception (v4). A plain class rename would wrongly
     * produce Phalcon\Dispatcher\AbstractDispatcher::EXCEPTION_*.
     *
     * @var string[]
     */
    public const EXCEPTION = [
        'EXCEPTION_NO_DI',
        'EXCEPTION_CYCLIC_ROUTING',
        'EXCEPTION_HANDLER_NOT_FOUND',
        'EXCEPTION_INVALID_HANDLER',
        'EXCEPTION_INVALID_PARAMS',
        'EXCEPTION_ACTION_NOT_FOUND',
    ];
}
