<?php

use Phalcon\Validation;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\StringLength;

final class SignupForm
{
    /**
     * @return Phalcon\Validation\Validator\PresenceOf
     */
    private function presence(): object
    {
        return new PresenceOf(['message' => 'required']);
    }

    public function rules(Validation $validation): Validation
    {
        $validation->add('email', new Email(['message' => 'invalid email']));
        $validation->add('name', $this->presence());
        $validation->add('phone', new Regex(['pattern' => '/^1/']));
        $validation->add('code', new StringLength(['max' => 10]));

        return $validation;
    }
}
-----
<?php

use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\Regex;
use Phalcon\Filter\Validation\Validator\StringLength;

final class SignupForm
{
    /**
     * @return Phalcon\Filter\Validation\Validator\PresenceOf
     */
    private function presence(): object
    {
        return new PresenceOf(['message' => 'required']);
    }

    public function rules(Validation $validation): Validation
    {
        $validation->add('email', new Email(['message' => 'invalid email']));
        $validation->add('name', $this->presence());
        $validation->add('phone', new Regex(['pattern' => '/^1/']));
        $validation->add('code', new StringLength(['max' => 10]));

        return $validation;
    }
}
