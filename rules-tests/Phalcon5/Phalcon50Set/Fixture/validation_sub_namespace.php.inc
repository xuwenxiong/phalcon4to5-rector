<?php

use Phalcon\Validation;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\PresenceOf;

final class SignupForm
{
    /**
     * @param Phalcon\Validation $validation
     * @return Phalcon\Validation\Validator\PresenceOf
     */
    public function build(object $validation): object
    {
        $validation->add('email', new Email(['message' => 'invalid email']));

        return new PresenceOf(['message' => 'required']);
    }
}
-----
<?php

use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\PresenceOf;

final class SignupForm
{
    /**
     * @param Phalcon\Filter\Validation $validation
     * @return Phalcon\Filter\Validation\Validator\PresenceOf
     */
    public function build(object $validation): object
    {
        $validation->add('email', new Email(['message' => 'invalid email']));

        return new PresenceOf(['message' => 'required']);
    }
}
