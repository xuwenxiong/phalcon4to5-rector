<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

use Phalcon\Exception as PException;

final class AliasedExceptionUsages
{
    /**
     * @throws PException
     */
    public function run(): void
    {
        try {
            $this->work();
        } catch (PException $e) {
            throw new PException($e->getMessage());
        }
    }

    private function work(): void
    {
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Phalcon50Set\Fixture;

use Exception;

final class AliasedExceptionUsages
{
    /**
     * @throws Exception
     */
    public function run(): void
    {
        try {
            $this->work();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function work(): void
    {
    }
}
