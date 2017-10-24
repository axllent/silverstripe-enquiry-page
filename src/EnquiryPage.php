<?php

namespace Axllent\EnquiryPage;

use Axllent\EnquiryPage\Model\EnquiryFormField;
use Page;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ArrayData;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;

/**
 * SilverStripe Enquiry Form
 * =========================
 *
 * Module to add a simple configurable enquiry form to SilverStripe
 *
 * License: MIT-style license http://opensource.org/licenses/MIT
 * Authors: Techno Joy development team (www.technojoy.co.nz)
 */

class EnquiryPage extends Page
{

    private static $table_name = 'EnquiryPage';

    private static $captcha_img_height = 30; // default verification image height

    private static $js_validation = false; // add JavaScript field validation

    private static $random_string = '3HNbhqWBEg'; // random string

    private static $icon = 'axllent/silverstripe-enquiry-page: images/EnquiryPage.png';

    private static $description = 'Page with an editable contact form';

    private static $db = [
        'EmailTo' => 'Varchar(254)',
        'EmailBcc' => 'Varchar(254)',
        'EmailFrom' => 'Varchar(254)',
        'EmailSubject' => 'Varchar(254)',
        'EmailSubmitButtonText' => 'Varchar(50)',
        'EmailSubmitCompletion' => 'HTMLText',
        'AddCaptcha' => 'Boolean',
        'CaptchaText' => 'Varchar(50)',
        'CaptchaHelp' => 'Varchar(100)',
    ];

    private static $has_many = [
        'EnquiryFormFields' => EnquiryFormField::class,
    ];

    private static $defaults = [
        'EmailSubject' => 'Website Enquiry',
        'EmailSubmitButtonText' => 'Submit Enquiry',
        'EmailSubmitCompletion' => "<p>Thanks, we've received your enquiry and will respond as soon as we're able.</p>",
        'CaptchaText' => 'Verification Image'
    ];

    protected $usedFields = [];

    protected $usedFieldCounter = 0;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $gridFieldConfig = GridFieldConfig_RecordEditor::create(100);
        $gridFieldConfig->addComponent(new GridFieldSortableRows('SortOrder'));

        $gridField = GridField::create('EnquiryFormFields', 'Enquiry Form Fields', $this->EnquiryFormFields(), $gridFieldConfig);
        $fields->addFieldToTab('Root.EnquiryForm', $gridField);

        if (!$this->CaptchaText) {
            $this->CaptchaText = 'Verification Image';
        }

        $email_settings = [
            EmailField::create('EmailTo', 'Send Email To')
                ->setAttribute('placeholder', 'you@yourdomain.com'),
            EmailField::create('EmailFrom', 'Send Email From')
                ->setAttribute('placeholder', 'website@yourdomain.com')
                ->setRightTitle('For example website@yourdomain.com'),
            TextField::create('EmailSubject', 'Email Subject'),
            HTMLEditorField::create('EmailSubmitCompletion', 'Message Once Completed')
                ->setRows(10)
                ->addExtraClass('stacked'),
            EmailField::create('EmailBcc', 'BCC Copy (optional)')
                ->setRightTitle('If you would like a copy of the enquiry to be sent elsewhere'),
            TextField::create('EmailSubmitButtonText', 'Submit Button Text'),
            HeaderField::create('CaptchaHdr', 'Form Captcha'),
            DropdownField::create('AddCaptcha', 'Captcha Image', [
                    1 => 'Yes add a captcha image',
                    0 => 'No'
            ])->setRightTitle('Add an anti-spam "captcha" image. ' .
			'This adds a small image with 4 random numbers which needs to be filled in correctly.'),
            TextField::create('CaptchaText', 'Field Name'),
            TextField::create('CaptchaHelp', 'Captcha Help')
                ->setRightTitle('Optionally add an explanation of what the captcha field is')
        ];

        $toggleSettings = ToggleCompositeField::create('FormSettings', 'Enquiry Form Settings',
            $email_settings
        );

        $fields->addFieldsToTab('Root.EnquiryForm', $toggleSettings);
        return $fields;
    }

    /*
     * Generate a unique key for that field
     * @param name, order
     * @return string
     */
    protected function keyGen($n, $s)
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
        return DBHTMLText::create()->setValue(implode("<br />\n", $build));
    }

    public function dataToHtml($str)
    {
        return DBHTMLText::create()->setValue(nl2br(htmlspecialchars(trim($str))));
    }

    public function getTemplateData($data)
    {
        $elements = $this->EnquiryFormFields();
        $templateData = [];
        $templateData['EmailData'] = ArrayList::create();
        foreach ($elements as $el) {
            $key = $this->keyGen($el->FieldName, $el->SortOrder);
            if ($el->FieldType == 'Header') {
                $templateData['EmailData']->push(
                    ArrayData::create([
                        'Header' => htmlspecialchars($el->FieldName), 'Type' => 'Header'
                    ])
                );
            } elseif (
                !in_array($el->FieldType, ['Header', 'Note']) &&
                isset($data[$key]) && $data[$key] != ''
            ) {
                $hdr = htmlspecialchars($el->FieldName);
                if (is_array($data[$key])) {
                    $value = $this->arrayToHtml($data[$key]);
                } else {
                    $value = $this->dataToHtml($data[$key]);
                }

                $templateData['EmailData']->push(
                    ArrayData::create([
                        'Header' => $hdr,
                        'Value' => $value,
                        'Type' => $el->FieldType
                    ])
                );
            }
        }

        return $templateData;
    }
}
