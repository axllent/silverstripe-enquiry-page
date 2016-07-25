<?php
/**
 * SilverStripe Enquiry Form
 * =========================
 *
 * Module to add a simple configurable enquiry form to SilverStripe 3
 *
 * License: MIT-style license http://opensource.org/licenses/MIT
 * Authors: Techno Joy development team (www.technojoy.co.nz)
 */

class EnquiryPage extends Page
{

    public static $icon = 'silverstripe-enquiry-page/images/EnquiryPage.png';

    public static $description = 'Page with an editable contact form';

    private static $db = array(
        'EmailTo' => 'Varchar(254)',
        'EmailBcc' => 'Varchar(254)',
        'EmailFrom' => 'Varchar(254)',
        'EmailSubject' => 'Varchar(254)',
        'EmailSubmitButtonText' => 'Varchar(50)',
        'EmailSubmitCompletion' => 'HTMLText',
        'AddCaptcha' => 'Boolean',
        'CaptchaText' => 'Varchar(50)',
        'CaptchaHelp' => 'Varchar(100)',
    );

    private static $has_many = array(
        'EnquiryFormFields' => 'EnquiryFormField',
    );


    private static $defaults = array(
        'EmailSubject' => 'Website Enquiry',
        'EmailSubmitButtonText' => 'Submit Enquiry',
        'EmailSubmitCompletion' => "<p>Thanks, we've received your enquiry and will respond as soon as we're able.</p>",
        'CaptchaText' => 'Verification Image'
    );

    protected $usedFields = array();

    protected $usedFieldCounter = 0;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.EnquiryForm', HeaderField::create('Enquiry Form Setup', 2));


        $gridFieldConfig = GridFieldConfig_RecordEditor::create(100);
        $gridFieldConfig->addComponent(new GridFieldSortableRows('SortOrder'));

        /* Unset field-sorting hack */
        $gridFieldConfig->getComponentByType('GridFieldSortableHeader')->setFieldSorting(
            array('FieldName'=>'FieldNameNoSorting', 'FieldType' => 'FieldTypeNoSorting')
        );

        $gridField = GridField::create('EnquiryFormFields', false, $this->EnquiryFormFields(), $gridFieldConfig);
        $fields->addFieldToTab('Root.EnquiryForm', $gridField);

        $email_settings = array(
            EmailField::create('EmailTo', 'Send email to'),
            EmailField::create('EmailFrom', 'Send email from')
                ->setRightTitle('For example website@yourdomain.com'),
            TextField::create('EmailSubject', 'Email subject'),
            HeaderField::create('Message on website once completed', 5),
            HTMLEditorField::create('EmailSubmitCompletion', '')
                ->setRows(10),
            EmailField::create('EmailBcc', 'Send BCC copy to (optional)')
                ->setRightTitle('If you would like a copy of the enquiry to be sent elsewhere, fill that in here.'),
            TextField::create('EmailSubmitButtonText', 'Submit button text')
        );

        $toggleSettings = ToggleCompositeField::create('FormSettings', 'Enquiry Form Settings',
            $email_settings
        );

        $fields->addFieldsToTab('Root.EnquiryForm', $toggleSettings);

        $spam_settings = array();

        array_push($spam_settings,
            DropdownField::create('AddCaptcha', 'Add captcha image (optional)', array(0 => 'No', 1 => 'Yes'))
                ->setRightTitle('You can optionally enable an anti-spam "captcha" image.
			             This adds a small image with 4 random numbers which needs to be filled in correctly.')
        );

        if (!$this->CaptchaText) {
            $this->CaptchaText = 'Verification Image';
        }
        array_push($spam_settings, TextField::create('CaptchaText', 'Field name'));

        array_push($spam_settings,
            TextField::create('CaptchaHelp', 'Captcha help (optional)')
                ->setRightTitle('If you would like to explain what the captcha is, please explain briefly what it is.
                    This is only used if you have selected to add the captcha image.')
        );

        $toggleSpam = ToggleCompositeField::create('SpamSettings', 'Anti-Spam Settings',
            $spam_settings
        );

        $fields->addFieldsToTab('Root.EnquiryForm', $toggleSpam);

        return $fields;
    }

    /*
     * Generate a unique key for that field
     * @param name, order
     * @return string
     */
    public function keyGen($n, $s)
    {
        return preg_replace('/[^a-z0-9]/i', '', $n) . '_' . $s;
    }

    public function validate()
    {
        $valid = parent::validate();

        if ($this->EmailSubmitButtonText == '') {
            $this->EmailSubmitButtonText = 'Submit Enquiry';
        }

        if ($this->CaptchaText == '') {
            $this->CaptchaText = 'Verification Image';
        }

        return $valid;
    }

    public function arrayToHtml($arr)
    {
        foreach ($arr as $a) {
            $build[] = '&middot; '.trim(htmlspecialchars($a));
        }
        return implode("<br />\n", $build);
    }

    public function dataToHtml($str)
    {
        return nl2br(htmlspecialchars(trim($str)));
    }

    public function getTemplateData($data)
    {
        $elements = $this->EnquiryFormFields();
        $templateData = array();
        $templateData['EmailData'] = ArrayList::create();
        foreach ($elements as $el) {
            $key = $this->keyGen($el->FieldName, $el->SortOrder);
            if ($el->FieldType == 'Header') {
                $templateData['EmailData']->push(
                    ArrayData::create(array('Header' => htmlspecialchars($el->FieldName), 'Type' => 'Header'))
                );
            } elseif (
                !in_array($el->FieldType, array('Header', 'Note')) &&
                isset($data[$key]) && $data[$key] != ''
            ) {
                $hdr = htmlspecialchars($el->FieldName);
                if (is_array($data[$key])) {
                    $value = $this->arrayToHtml($data[$key]);
                } else {
                    $value = $this->dataToHtml($data[$key]);
                }

                $templateData['EmailData']->push(
                    ArrayData::create(array('Header' => $hdr, 'Value' => $value, 'Type' => $el->FieldType))
                );
            }
        }

        return $templateData;
    }
}

class EnquiryPage_Controller extends Page_Controller
{

    private static $allowed_actions = array('EnquiryForm', 'captcha');

    public function EnquiryForm()
    {
        if (
            !Email::validEmailAddress($this->EmailTo) ||
            !Email::validEmailAddress($this->EmailFrom)
        ) {
            return false;
        }

        if (!$this->EmailSubject) {
            $this->EmailSubject = 'Website Enquiry';
        }

        $elements = $this->EnquiryFormFields();

        /* empty form, return nothing */
        if ($elements->count() == 0) {
            return false;
        }

        /* Build the fieldlist */
        $fields = FieldList::create();
        $validator = RequiredFields::create();
        $jsValidator = array();

        foreach ($elements as $el) {
            $key = $this->keyGen($el->FieldName, $el->SortOrder);
            $field = false;
            $type = false;
            if ($el->FieldType == 'Text') {
                if ($el->FieldOptions == 1) {
                    $field = TextField::create($key, htmlspecialchars($el->FieldName));
                } else {
                    $field = TextareaField::create($key, htmlspecialchars($el->FieldName));
                    $field->setRows($el->FieldOptions);
                }
            } elseif ($el->FieldType == 'Email') {
                $field = EmailField::create($key, htmlspecialchars($el->FieldName));
            } elseif ($el->FieldType == 'Select') {
                $options = preg_split('/\n\r?/', $el->FieldOptions, -1, PREG_SPLIT_NO_EMPTY);
                if (count($options) > 0) {
                    $tmp = array();
                    foreach ($options as $o) {
                        $tmp[trim($o)] = trim($o);
                    }
                    $field = DropdownField::create($key, htmlspecialchars($el->FieldName), $tmp);
                    $field->setEmptyString('[ Please Select ]');
                }
            } elseif ($el->FieldType == 'Checkbox') {
                $options = preg_split('/\n\r?/', $el->FieldOptions, -1, PREG_SPLIT_NO_EMPTY);
                if (count($options) > 0) {
                    $tmp = array();
                    foreach ($options as $o) {
                        $tmp[trim($o)] = trim($o);
                    }
                    $field = CheckboxSetField::create($key, htmlspecialchars($el->FieldName), $tmp);
                }
            } elseif ($el->FieldType == 'Radio') {
                $options = preg_split('/\n\r?/', $el->FieldOptions, -1, PREG_SPLIT_NO_EMPTY);
                if (count($options) > 0) {
                    $tmp = array();
                    foreach ($options as $o) {
                        $tmp[trim($o)] = trim($o);
                    }
                    $field = OptionsetField::create($key, htmlspecialchars($el->FieldName), $tmp);
                }
            } elseif ($el->FieldType == 'Header') {
                if ($el->FieldOptions) {
                    $field = LiteralField::create(htmlspecialchars($el->FieldName),
                        '<h4>' . htmlspecialchars($el->FieldName) . '</h4>
						<p class="note">'.nl2br(htmlspecialchars($el->FieldOptions)).'</p>'
                    );
                } else {
                    $field = HeaderField::create(htmlspecialchars($el->FieldName), 4);
                }
            } elseif ($el->FieldType == 'Note') {
                if ($el->FieldOptions) {
                    $field = LiteralField::create(htmlspecialchars($el->FieldName), '<p class="note">'.nl2br(htmlspecialchars($el->FieldOptions)).'</p>');
                } else {
                    $field = LiteralField::create(htmlspecialchars($el->FieldName), '<p class="note">'.htmlspecialchars($el->FieldName).'</p>');
                }
            }

            if ($field) {
                /* Allow using $_GET to pre-populate fields */
                $request = $this->request;
                if (
                    !$request->isPOST() &&
                    !$field->Value() &&
                    null != $request->getVar($el->FieldName)
                ) {
                    $field->setValue($request->getVar($el->FieldName));
                }

                if ($el->RequiredField == 1) {
                    $field->addExtraClass('required');
                    /* Add "Required" next to field" */
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

            $validator->addRequiredField('CaptchaImage');
            $jsValidator['CaptchaImage'] = 'Text';

            if ($this->CaptchaHelp) {
                $field->setRightTitle('<span id="CaptchaHelp">'.htmlspecialchars($this->CaptchaHelp).'</span>');
            }

            $fields->push($field);
        }

        $actions = FieldList::create(
            FormAction::create('SendEnquiryForm', $this->EmailSubmitButtonText)
        );

        Requirements::customScript('var EnquiryFormValidator='.json_encode($jsValidator).';');
        Requirements::javascript(
            basename(dirname(dirname(__FILE__))) . '/templates/javascript/EnquiryForm.js'
        );

        $form = Form::create($this, 'EnquiryForm', $fields, $actions, $validator);
        return $form;
    }


    public function SendEnquiryForm($data, $form)
    {
        $From = $this->EmailFrom;
        $To = $this->EmailTo;
        $Subject = $this->EmailSubject;
        $email = new Email($From, $To, $Subject);
        $replyTo = $this->EnquiryFormFields()->filter(array('FieldType' => 'Email'))->First();
        if ($replyTo) {
            $postField = $this->keyGen($replyTo->FieldName, $replyTo->SortOrder);
            if (isset($data[$postField]) && Email::validEmailAddress($data[$postField])) {
                $email->replyTo($data[$postField]);
            }
        }
        if ($this->EmailBcc) {
            $email->setBcc($this->EmailBcc);
        }
        //abuse / tracking
        $email->addCustomHeader('X-Sender-IP', $_SERVER['REMOTE_ADDR']);
        //set template
        $email->setTemplate('EnquiryFormEmail');
        //populate template
        $templateData = $this->getTemplateData($data);
        $email->populateTemplate($templateData);
        //send mail
        $email->send();
        //return to submitted message
        if (Director::is_ajax()) {
            return $this->renderWith('EnquiryPageAjaxSuccess');
        }
        $this->redirect($this->Link('?success=1#thankyou'));
    }

    public function Success()
    {
        return isset($_GET['success']);
    }

    /**
     * Captcha image generated on the fly
     */
    public function captcha()
    {
        $this->response = new SS_HTTPResponse();
        $this->response->addHeader('Content-Type', 'image/jpeg');
        $width = 60;
        $height = 24;
        $my_image = imagecreatetruecolor($width, $height);
        imagefill($my_image, 0, 0, 0xFFFFFF);
        $purple = imageColorAllocate($my_image, 200, 0, 255);
        $black = imageColorAllocate($my_image, 255, 255, 255);
        $green = imageColorAllocate($my_image, 22, 255, 2);
        $random_colours = array($purple,$green,$black);
        // add noise
        for ($c = 0; $c < 150; $c++) {
            $x = rand(0, $width-1);
            $y = rand(0, $height-1);
            imagesetpixel($my_image, $x, $y, $random_colours[array_rand($random_colours)]);
        }
        $x = rand(1, 10);
        $rand_string = rand(1000, 9999);
        $numbers = str_split($rand_string);
        foreach ($numbers as $number) {
            $y = rand(1, 10);
            imagestring($my_image, 5, $x, $y, $number, 0x000000);
            $x = $x+12;
        }
        Session::set('customcaptcha', md5($rand_string.$_SERVER['REMOTE_ADDR']).'a4xn');
        $this->response->setBody(imagejpeg($my_image));
        imagedestroy($my_image);
        return $this->response;
    }

    public function getCaptchaLabel()
    {
        return ($this->CaptchaText) ? $this->CaptchaText : 'Verification Image';
    }
}
