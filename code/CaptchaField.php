<?php

class CaptchaField extends TextField {

	function __construct($name, $title = null, $value = "", $form = null){
		parent::__construct($name, $title, $value, $form);
	}

	function Field($properties = array()) {
		$attributes = array(
			'type' => 'text',
			'class' => 'CaptchaField',
			'id' => $this->id(),
			'name' => $this->getName(),
			'value' => $this->Value(),
			'tabindex' => $this->getAttribute("tabindex"),
			'maxlength' => 4,
			'size' => 30
		);

		// create link to image to display code
		$html =  '<img src="' . _EnquiryPageBase .'/images/captcha.jpg?' . time() . '" class="customcaptcha-image" alt="CAPTCHA security code" />';

		// create input field
		$html .= $this->createTag('input', $attributes);

		return $html;

	}


	// SERVER-SIDE VALIDATION (to ensure a browser with javascript disabled doesn't bypass validation)

	function validate($validator){

		$this->value = trim($this->value);

		$SessionCaptcha = Session::get('customcaptcha');

		if ( md5(trim($this->value).$_SERVER['REMOTE_ADDR']).'a4xn' != $SessionCaptcha ) {
			$validator->validationError(
				$this->name,
				'Codes do not match, please try again',
				'required'
			);
		}

	}
}