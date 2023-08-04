<?php

namespace Axllent\EnquiryPage\Forms;

use Axllent\EnquiryPage\EnquiryPage;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\TextField;

class CaptchaField extends TextField
{
    /**
     * Get attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = [];

        $attributes['type']         = 'number';
        $attributes['autocomplete'] = 'off';
        $attributes['required']     = 'required';

        return array_merge(
            parent::getAttributes(),
            $attributes
        );
    }

    /**
     * Get validation image URL
     *
     * @return string
     */
    public function validationImageURL()
    {
        return $this->getForm()->getController()
            ->Link('captcha.jpg') . '?' . time();
    }

    /**
     * Server-side validation
     *
     * @param ValidationResult $validator Validator
     *
     * @return ValidationResult
     */
    public function validate($validator)
    {
        $typed     = EnquiryPage::getHash($this->value);
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
