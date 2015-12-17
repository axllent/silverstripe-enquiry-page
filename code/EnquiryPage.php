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

        $fields->addFieldToTab('Root.EnquiryForm', new HeaderField('Enquiry Form Setup', 2));


        $gridFieldConfig = GridFieldConfig_RecordEditor::create(100);
        $gridFieldConfig->addComponent(new GridFieldSortableRows('SortOrder'));

        /* Unset sorting hack */
        $gridFieldConfig->getComponentByType('GridFieldSortableHeader')->setFieldSorting(
            array('FieldName'=>'FieldNameNoSorting', 'FieldType' => 'FieldTypeNoSorting')
        );

        $gridField = new GridField('EnquiryFormFields', false, $this->EnquiryFormFields(), $gridFieldConfig);
        $fields->addFieldToTab('Root.EnquiryForm', $gridField);

        $emailSettings = array();
        array_push($emailSettings, new EmailField('EmailTo', 'Send email to'));
        array_push($emailSettings, new EmailField('EmailFrom', 'Send email from'));
        array_push($emailSettings, new TextField('EmailSubject', 'Email subject'));
        array_push($emailSettings, new HeaderField('Message on website once completed', 5));
        $editor = new HTMLEditorField('EmailSubmitCompletion', '');
        $editor->setRows(10);
        array_push($emailSettings, $editor);
        array_push($emailSettings, new LiteralField('BccHdr',
            '<p>If you would like a copy of the enquiry to be sent elsewhere, fill that in here.</p>'
        ));
        array_push($emailSettings, new EmailField('EmailBcc', 'Send BCC copy to (optional)'));
        array_push($emailSettings, new TextField('EmailSubmitButtonText', 'Submit button text'));

        $toggleSettings = ToggleCompositeField::create('FormSettings', 'Enquiry Form Settings',
            $emailSettings
        )->setHeadingLevel(5);

        $fields->addFieldsToTab('Root.EnquiryForm', $toggleSettings);

        $spamSettings = array();
        array_push($spamSettings, new LiteralField('SpamHdr',
            '<p>You can optionally enable an anti-spam "captcha" image.
			This adds a small image with 4 random numbers which needs to be filled in correctly.</p>'
        ));
        array_push($spamSettings, new DropdownField('AddCaptcha', 'Add captcha image (optional)', array(0 => 'No', 1 => 'Yes')));

        if (!$this->CaptchaText) {
            $this->CaptchaText = 'Verification Image';
        }
        array_push($spamSettings, new TextField('CaptchaText', 'Field name'));

        array_push($spamSettings, new LiteralField('CaptchaInfo',
            '<p>If you would like to explain what the captcha is, please explain briefly what it is. This is only used if
			 you have selected to add the captcha image above.</p>'
        ));
        array_push($spamSettings, new TextField('CaptchaHelp', 'Captcha help (optional)'));

        $toggleSpam = ToggleCompositeField::create('SpamSettings', 'Anti-Spam Settings',
            $spamSettings
        )->setHeadingLevel(5);

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
        $templateData['EmailData'] = new ArrayList();
        foreach ($elements as $el) {
            $key = $this->keyGen($el->FieldName, $el->SortOrder);
            if ($el->FieldType == 'Header') {
                $templateData['EmailData']->push(
                    new ArrayData(array('Header' => htmlspecialchars($el->FieldName), 'Type' => 'Header'))
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
                    new ArrayData(array('Header' => $hdr, 'Value' => $value, 'Type' => $el->FieldType))
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

        if ($elements->count() == 0) {
            return false;
        }

        /* Build the fieldlist */
        $fields = new FieldList();
        $validator = new RequiredFields();
        $jsValidator = array();

        foreach ($elements as $el) {
            $key = $this->keyGen($el->FieldName, $el->SortOrder);
            $field = false;
            $type = false;
            if ($el->FieldType == 'Text') {
                if ($el->FieldOptions == 1) {
                    $field = new TextField($key, htmlspecialchars($el->FieldName));
                } else {
                    $field = new TextareaField($key, htmlspecialchars($el->FieldName));
                    $field->setRows($el->FieldOptions);
                }
            } elseif ($el->FieldType == 'Email') {
                $field = new EmailField($key, htmlspecialchars($el->FieldName));
            } elseif ($el->FieldType == 'Select') {
                $options = preg_split('/\n\r?/', $el->FieldOptions, -1, PREG_SPLIT_NO_EMPTY);
                if (count($options) > 0) {
                    $tmp = array();
                    foreach ($options as $o) {
                        $tmp[trim($o)] = trim($o);
                    }
                    $field = new DropdownField($key, htmlspecialchars($el->FieldName), $tmp);
                    $field->setEmptyString('[ Please Select ]');
                }
            } elseif ($el->FieldType == 'Checkbox') {
                $options = preg_split('/\n\r?/', $el->FieldOptions, -1, PREG_SPLIT_NO_EMPTY);
                if (count($options) > 0) {
                    $tmp = array();
                    foreach ($options as $o) {
                        $tmp[trim($o)] = trim($o);
                    }
                    $field = new CheckboxSetField($key, htmlspecialchars($el->FieldName), $tmp);
                }
            } elseif ($el->FieldType == 'Radio') {
                $options = preg_split('/\n\r?/', $el->FieldOptions, -1, PREG_SPLIT_NO_EMPTY);
                if (count($options) > 0) {
                    $tmp = array();
                    foreach ($options as $o) {
                        $tmp[trim($o)] = trim($o);
                    }
                    $field = new OptionsetField($key, htmlspecialchars($el->FieldName), $tmp);
                }
            } elseif ($el->FieldType == 'Header') {
                if ($el->FieldOptions) {
                    $field = new LiteralField(htmlspecialchars($el->FieldName),
                        '<h4>' . htmlspecialchars($el->FieldName) . '</h4>
						<p class="note">'.nl2br(htmlspecialchars($el->FieldOptions)).'</p>'
                    );
                } else {
                    $field = new HeaderField(htmlspecialchars($el->FieldName), 4);
                }
            } elseif ($el->FieldType == 'Note') {
                if ($el->FieldOptions) {
                    $field = new LiteralField(htmlspecialchars($el->FieldName), '<p class="note">'.nl2br(htmlspecialchars($el->FieldOptions)).'</p>');
                } else {
                    $field = new LiteralField(htmlspecialchars($el->FieldName), '<p class="note">'.htmlspecialchars($el->FieldName).'</p>');
                }
            }

            if ($field) {
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
            $field = new CaptchaField('CaptchaImage', $label);
            $field->addExtraClass('required');

            $validator->addRequiredField('CaptchaImage');
            $jsValidator['CaptchaImage'] = 'Text';

            if ($this->CaptchaHelp) {
                $field->setRightTitle('<span id="CaptchaHelp">'.htmlspecialchars($this->CaptchaHelp).'</span>');
            }

            $fields->push($field);
        }

        $actions = new FieldList(
            new FormAction('SendEnquiryForm', $this->EmailSubmitButtonText)
        );

        Requirements::customScript("var EnquiryFormValidator=".json_encode($jsValidator).';');
        Requirements::javascript(
            basename(dirname(dirname(__FILE__))) . "/templates/javascript/EnquiryForm.js"
        );

        $form = new Form($this, 'EnquiryForm', $fields, $actions, $validator);
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
        $this->redirect($this->Link("?success=1#thankyou"));
    }

    public function Success()
    {
        return isset($_GET['success']);
    }

    /*
     * Captcha image
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
