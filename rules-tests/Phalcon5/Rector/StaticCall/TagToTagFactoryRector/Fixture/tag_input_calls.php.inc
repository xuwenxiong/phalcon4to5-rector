<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\StaticCall\TagToTagFactoryRector\Fixture;

final class TagInputCalls
{
    public function run(): void
    {
        $name = \Phalcon\Tag::textField(['name', 'value' => 'x', 'class' => 'c']);
        $email = \Phalcon\Tag::emailField('email');
        $hidden = \Phalcon\Tag::hiddenField('csrf');
        $password = \Phalcon\Tag::passwordField(['pwd', 'placeholder' => 'Password']);
        $area = \Phalcon\Tag::textArea(['bio', 'cols' => '6', 'rows' => '20']);
        $check = \Phalcon\Tag::checkField(['subscribe', 'value' => 'yes']);
        $radio = \Phalcon\Tag::radioField(['plan', 'value' => 'pro']);
        $submit = \Phalcon\Tag::submitButton('Save');
        $color = \Phalcon\Tag::colorField('theme');
        $date = \Phalcon\Tag::dateField(['start', 'value' => '2026-01-01']);
        $slug = \Phalcon\Tag::friendlyText('Some Title', '-', true);
        $doctype = \Phalcon\Tag::HTML5;
    }
}
-----
<?php

namespace Phalcon4To5\Rector\Tests\Phalcon5\Rector\StaticCall\TagToTagFactoryRector\Fixture;

use Phalcon\Html\Helper\Doctype;

final class TagInputCalls
{
    public function run(): void
    {
        $name = $tagFactory->inputText('name', 'x', ['class' => 'c']);
        $email = $tagFactory->inputEmail('email');
        $hidden = $tagFactory->inputHidden('csrf');
        $password = $tagFactory->inputPassword('pwd', null, ['placeholder' => 'Password']);
        $area = $tagFactory->inputTextarea('bio', null, ['cols' => '6', 'rows' => '20']);
        $check = $tagFactory->inputCheckbox('subscribe', 'yes');
        $radio = $tagFactory->inputRadio('plan', 'pro');
        $submit = $tagFactory->inputSubmit('Save');
        $color = $tagFactory->inputColor('theme');
        $date = $tagFactory->inputDate('start', '2026-01-01');
        $slug = $tagFactory->friendlyTitle('Some Title', '-', true);
        $doctype = Doctype::HTML5;
    }
}
