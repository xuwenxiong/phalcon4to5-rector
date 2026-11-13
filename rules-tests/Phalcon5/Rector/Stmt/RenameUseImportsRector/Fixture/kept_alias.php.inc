<?php

use Phalcon\Exception as PException;
use Phalcon\Crypt as CryptX;
use App\Support\Exception;

final class KeptAlias
{
    public function run(): void
    {
        try {
            $this->work();
        } catch (PException $e) {
            throw new Exception('failed', 0, $e);
        }
    }

    private function work(): void
    {
    }
}
-----
<?php

use Exception as PException;
use Phalcon\Encryption\Crypt as CryptX;
use App\Support\Exception;

final class KeptAlias
{
    public function run(): void
    {
        try {
            $this->work();
        } catch (PException $e) {
            throw new Exception('failed', 0, $e);
        }
    }

    private function work(): void
    {
    }
}
