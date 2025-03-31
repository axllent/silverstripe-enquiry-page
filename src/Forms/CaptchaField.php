<?php

namespace Axllent\EnquiryPage\Forms;

use Axllent\EnquiryPage\EnquiryPage;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Validation\ValidationResult;
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
     */
    public function validate(): ValidationResult
    {
        $valid     = parent::validate();
        $typed     = EnquiryPage::getHash($this->value);
        $generated = Controller::curr()
            ->getRequest()
            ->getSession()
            ->get('customcaptcha');

        if ($typed != $generated) {
            $valid->addFieldError(
                $this->name,
                'Code does not match, please try again',
                'required'
            );
            $this->value = '';
        }

        return $valid;
    }
}
