<?php

use Phalcon\Validation;
use Phalcon\Validation\Validator\StringLength;

final class FeedbackForm
{
    public function rules(): array
    {
        return [
            'feedback' => new StringLength\Max(['max' => 400]),
            'title' => new StringLength\Min(['min' => 2]),
        ];
    }
}
-----
<?php

use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\StringLength;

final class FeedbackForm
{
    public function rules(): array
    {
        return [
            'feedback' => new StringLength\Max(['max' => 400]),
            'title' => new StringLength\Min(['min' => 2]),
        ];
    }
}
