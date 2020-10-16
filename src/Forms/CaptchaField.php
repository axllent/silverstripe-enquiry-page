<?php

namespace Axllent\EnquiryPage\Forms;

use \Axllent\EnquiryPage\EnquiryPage;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;
use SilverStripe\Forms\TextField;

class CaptchaField extends TextField
{
    /**
     * @return array
     */
    public function getAttributes()
    {
        $attributes = [];

        $attributes['type'] = 'number';
        $attributes['autocomplete'] = 'off';
        $attributes['required'] = 'required';

        return array_merge(
            parent::getAttributes(),
            $attributes
        );
    }

    public function validationImageURL()
    {
        return $this->getForm()->getController()->Link() .'captcha.jpg?' . time();
    }

    /*
     * SERVER-SIDE VALIDATION (to ensure a browser with javascript disabled doesn't bypass validation)
     */
    public function validate($validator)
    {
        $typed = EnquiryPage::get_hash($this->value);
        $generated = Controller::curr()
            ->getRequest()
            ->getSession()
            ->get('customcaptcha');

        if ($typed != $generated) {
            $validator->validationError(
                $this->name,
                'Codes do not match, please try again',
                'required'
            );
            $this->value = '';
        }
    }
}
