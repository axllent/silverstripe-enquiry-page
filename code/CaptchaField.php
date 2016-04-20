<?php

class CaptchaField extends TextField
{

    public function __construct($name, $title = null, $value = '', $form = null)
    {
        parent::__construct($name, $title, $value, $form);
    }

    public function Field($properties = array())
    {
        $attributes = array(
            'type' => 'text',
            'class' => 'CaptchaField',
            'id' => $this->id(),
            'name' => $this->getName(),
            'value' => $this->Value(),
            'required' => 'required',
            'tabindex' => $this->getAttribute("tabindex"),
            'maxlength' => 4,
            'size' => 30,
            'autocomplete' => 'off'
        );

        // create link to image to display code
        $html =  '<img src="' . $this->getForm()->getController()->Link() .'captcha.jpg?' . time() . '" class="customcaptcha-image" alt="CAPTCHA security code" width="60" height="24" />';
        // create input field
        $html .= $this->createTag('input', $attributes);

        return $html;
    }

    /*
     * SERVER-SIDE VALIDATION (to ensure a browser with javascript disabled doesn't bypass validation)
     */
    public function validate($validator)
    {
        $this->value = trim($this->value);
        $SessionCaptcha = Session::get('customcaptcha');
        if (md5(trim($this->value).$_SERVER['REMOTE_ADDR']).'a4xn' != $SessionCaptcha) {
            $validator->validationError(
                $this->name,
                'Codes do not match, please try again',
                'required'
            );
            $this->value = '';
        }
    }
}
