<?php

use Phalcon\Exception as PException;

final class RedundantAlias
{
    /**
     * @throws PException
     */
    public function run(): void
    {
        try {
            $this->work();
        } catch (PException $e) {
            throw new PException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private function work(): void
    {
    }
}
-----
<?php

use Exception;

final class RedundantAlias
{
    /**
     * @throws Exception
     */
    public function run(): void
    {
        try {
            $this->work();
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private function work(): void
    {
    }
}
