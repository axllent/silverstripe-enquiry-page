<?php

namespace Axllent\EnquiryPage;

use Axllent\EnquiryPage\Forms\CaptchaField;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionSetField;
use SilverStripe\Forms\TextAreaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\View\Parsers\URLSegmentFilter;
use SilverStripe\View\Requirements;

class EnquiryPageController extends \PageController
{
    /**
     * Allowed actions
     *
     * @var array
     *
     * @config
     */
    private static $allowed_actions = [
        'EnquiryForm',
        'captcha',
    ];

    /**
     * Index
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return HTTPResponse
     */
    public function index($request)
    {
        return $this->renderWith(['EnquiryPage', 'Page']);
    }

    /**
     * Enquiry form
     *
     * @return Form
     */
    public function enquiryForm()
    {
        if (!$this->EmailTo || !$this->EmailFrom
            || !Email::is_valid_address($this->EmailTo)
            || !Email::is_valid_address($this->EmailFrom)
        ) {
            return false;
        }

        if (!$this->EmailSubject) {
            $this->EmailSubject = 'Website enquiry';
        }

        $elements = $this->EnquiryFormFields();

        // empty form, return nothing
        if (0 == $elements->count()) {
            return false;
        }

        // Build the FieldList
        $fields      = FieldList::create();
        $validator   = RequiredFieldsValidator::create();
        $jsValidator = [];

        // Create filter for possible $_GET parameters / pre-population
        $get_param_filter = URLSegmentFilter::create();

        foreach ($elements as $el) {
            $key   = $el->formFieldName();
            $field = false;
            $type  = false;
            if ('Text' == $el->FieldType) {
                if (1 == $el->FieldOptions) {
                    $field = TextField::create($key, $el->FieldName);
                } else {
                    $field = TextAreaField::create($key, $el->FieldName);
                    $field->setRows($el->FieldOptions);
                }
            } elseif ('Email' == $el->FieldType) {
                $field = EmailField::create($key, $el->FieldName);
            } elseif ('Select' == $el->FieldType) {
                $options = preg_split(
                    '/\n\r?/',
                    $el->FieldOptions,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                );
                if (count($options) > 0) {
                    $tmp = [];
                    foreach ($options as $o) {
                        $tmp[trim($o)] = trim($o);
                    }
                    $field = DropdownField::create($key, $el->FieldName, $tmp);
                    $field->setEmptyString('[ Please Select ]');
                }
            } elseif ('Checkbox' == $el->FieldType) {
                $options = preg_split(
                    '/\n\r?/',
                    $el->FieldOptions,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                );
                if (1 == count($options)) {
                    $field = CheckboxField::create($key, trim(reset($options)));
                } elseif (count($options) > 0) {
                    $tmp = [];
                    foreach ($options as $o) {
                        $tmp[trim($o)] = trim($o);
                    }
                    $field = CheckboxSetField::create($key, $el->FieldName, $tmp);
                }
            } elseif ('Radio' == $el->FieldType) {
                $options = preg_split(
                    '/\r?\n/',
                    $el->FieldOptions,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                );
                if (count($options) > 0) {
                    $tmp = [];
                    foreach ($options as $o) {
                        $tmp[trim($o)] = trim($o);
                    }
                    $field = OptionSetField::create($key, $el->FieldName, $tmp);
                }
            } elseif ('Header' == $el->FieldType) {
                // Readonly field
                $html  = ShortcodeParser::get_active()->parse($el->FieldOptions);
                $field = HTMLReadonlyField::create($key, $el->FieldName, $html);
            } elseif ('HTML' == $el->FieldType) {
                // backwards compatible for old values
                $html  = ShortcodeParser::get_active()->parse($el->FieldOptions);
                $field = LiteralField::create($key, $html);
            }

            if ($field) {
                // Allow $_GET parameters to pre-populate fields
                $request = $this->request;
                $get_var = $get_param_filter->filter($el->FieldName);

                if (!$request->isPOST()
                    && !$field->getValue()
                    && null != $request->getVar($get_var)
                ) {
                    $field->setValue($request->getVar($get_var));
                }

                if (1 == $el->RequiredField) {
                    $field->addExtraClass('required');
                    // add "Required" next to field
                    $validator->addRequiredField($key);
                    $jsValidator[$key] = $el->FieldType;
                }
                if ($el->PlaceholderText) {
                    $field->setAttribute('placeholder', $el->PlaceholderText);
                }
                $fields->push($field);
            }
        }

        if ($this->AddCaptcha) {
            $label = $this->CaptchaLabel;
            $field = CaptchaField::create('CaptchaImage', $label);
            $field->addExtraClass('required');
            $field->setTemplate('Forms/CaptchaField');

            $validator->addRequiredField('CaptchaImage');
            $jsValidator['CaptchaImage'] = 'Text';

            if ($this->CaptchaHelp) {
                $field->setRightTitle($this->CaptchaHelp);
            }

            $fields->push($field);
        }

        $actions = FieldList::create(
            FormAction::create('SendEnquiryForm', $this->EmailSubmitButtonText)
        );

        if (Config::inst()->get(
            'Axllent\EnquiryPage\EnquiryPage',
            'js_validation'
        )
        ) {
            Requirements::customScript(
                'var EnquiryFormValidator=' . json_encode($jsValidator) . ';'
            );
            Requirements::javascript(
                'axllent/silverstripe-enquiry-page: javascript/enquiryform.js'
            );
        }

        return Form::create($this, 'EnquiryForm', $fields, $actions, $validator);
    }

    /**
     * Send enquiry form
     *
     * @param array $data Form data
     * @param Form  $form Form
     *
     * @return HTTPResponse
     */
    public function sendEnquiryForm($data, $form)
    {
        $this->extend('onBeforeSendEnquiryForm', $data, $form);

        $from    = $this->EmailFrom;
        $to      = $this->EmailTo;
        $subject = $this->EmailSubject;
        $email   = Email::create($from, $to, $subject);

        // if one of the fieldfs is an email address, use that for reply-to
        $replyTo = $this->EnquiryFormFields()
            ->filter(['FieldType' => 'Email'])->first();
        if ($replyTo) {
            $post_field = $replyTo->formFieldName();
            if (isset($data[$post_field])
                && Email::is_valid_address($data[$post_field])
            ) {
                $email->setReplyTo($data[$post_field]);
            }
        }
        if ($this->EmailBcc) {
            $email->setBcc($this->EmailBcc);
        }

        // abuse / tracking
        if ($ip = EnquiryPage::getClientIP()) {
            $email->getHeaders()->setHeaderBody('Text', 'X-Sender-IP', $ip);
        }

        $templateData = $this->getTemplateData($data);
        $email->setData($templateData);

        if ($this->EmailPlain) {
            $email->setPlainTemplate('Email/EnquiryFormEmail_PlainText');
            $email->sendPlain();
        } else {
            $email->setHTMLTemplate('Email/EnquiryFormEmail');
            $email->send();
        }

        $this->extend('onAfterSendEnquiryForm', $data, $form);

        if (Director::is_ajax()) {
            return $this->renderWith('Layout/EnquiryPageAjaxSuccess');
        }
        $this->redirect($this->Link('?success=1#thankyou'));
    }

    /**
     * Success is for template purposes only
     *
     * @return bool
     */
    public function success()
    {
        return isset($_GET['success']);
    }

    /**
     * Captcha image generated on the fly
     *
     * @param HTTPRequest $request HTTPRequest
     *
     * @return HTTPResponse Image
     */
    public function captcha($request)
    {
        $this->response = new HTTPResponse();
        $this->response->addHeader('Content-Type', 'image/jpeg');
        $width  = 60;
        $height = Config::inst()
            ->get('Axllent\EnquiryPage\EnquiryPage', 'captcha_img_height');
        $my_image = imagecreatetruecolor($width, $height);
        imagefill($my_image, 0, 0, 0xFFFFFF);
        $purple         = imagecolorallocate($my_image, 200, 0, 255);
        $black          = imagecolorallocate($my_image, 255, 255, 255);
        $green          = imagecolorallocate($my_image, 22, 255, 2);
        $random_colours = [$purple, $green, $black];
        // add noise
        for ($c = 0; $c < 150; ++$c) {
            $x = rand(0, $width - 1);
            $y = rand(0, $height - 1);
            imagesetpixel(
                $my_image,
                $x,
                $y,
                $random_colours[array_rand($random_colours)]
            );
        }
        $x       = rand(1, 15);
        $token   = rand(1000, 9999);
        $numbers = str_split($token);
        foreach ($numbers as $number) {
            $y = rand(1, $height - 20);
            imagestring($my_image, 5, $x, $y, $number, 0x000000);
            $x = $x + 12;
        }
        $request->getSession()->set('customcaptcha', EnquiryPage::getHash($token));
        $this->response->setBody(imagejpeg($my_image));
        imagedestroy($my_image);

        return $this->response;
    }

    /**
     * Get captcha label
     *
     * @return string
     */
    public function getCaptchaLabel()
    {
        return ($this->CaptchaText) ? $this->CaptchaText : 'Verification image';
    }
}
