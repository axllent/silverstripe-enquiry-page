<?php

namespace Axllent\EnquiryPage;

use Axllent\EnquiryPage\Model\EnquiryFormField;
use Page;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\View\ArrayData;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class EnquiryPage extends \Page
{
    /**
     * Table name
     *
     * @var string
     */
    private static $table_name = 'EnquiryPage';

    /**
     * Default verification image height
     *
     * @var int
     */
    private static $captcha_img_height = 30;

    /**
     * Add JavaScript field validation
     *
     * @var mixed
     */
    private static $js_validation = false;

    /**
     * Looked up in $_SERVER
     *
     * @var string
     */
    private static $client_ip_fields = 'REMOTE_ADDR';

    /**
     * Random token string
     *
     * @var string
     */
    private static $random_string = '3HNbhqWBEg';

    /**
     * Page icon class
     *
     * @var string
     *
     * @config
     */
    private static $icon_class = 'font-icon-p-post';

    /**
     * Description
     *
     * @var string
     */
    private static $description = 'Page with an editable contact form';

    /**
     * Database field definitions.
     *
     * @var array
     *
     * @config
     */
    private static $db = [
        'EmailTo'               => 'Varchar(254)',
        'EmailBcc'              => 'Varchar(254)',
        'EmailFrom'             => 'Varchar(254)',
        'EmailSubject'          => 'Varchar(254)',
        'EmailSubmitButtonText' => 'Varchar(50)',
        'EmailSubmitCompletion' => 'HTMLText',
        'EmailPlain'            => 'Boolean',
        'AddCaptcha'            => 'Boolean',
        'CaptchaText'           => 'Varchar(50)',
        'CaptchaHelp'           => 'Varchar(100)',
    ];

    /**
     * Defines one-to-many relationships.
     *
     * @var array
     *
     * @config
     */
    private static $has_many = [
        'EnquiryFormFields' => EnquiryFormField::class,
    ];

    /**
     * DataObject defaults
     *
     * @var array
     *
     * @config
     */
    private static $defaults = [
        'EmailSubject'          => 'Website enquiry',
        'EmailSubmitButtonText' => 'Submit enquiry',
        'EmailSubmitCompletion' => "<p>Thanks, we've received your enquiry and will respond as soon as we're able.</p>",
        'EmailPlain'            => false,
        'CaptchaText'           => 'Verification image',
    ];

    /**
     * Used fields
     *
     * @var array
     */
    protected $usedFields = [];

    /**
     * Field counter
     *
     * @var int
     */
    protected $usedFieldCounter = 0;

    /**
     * Data administration interface in Silverstripe.
     *
     * @see    {@link ValidationResult}
     *
     * @return FieldList Returns a TabSet for usage within the CMS
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $config = GridFieldConfig_RecordEditor::create(100);
        if (class_exists(GridFieldOrderableRows::class)) {
            $config->addComponent(
                GridFieldOrderableRows::create('SortOrder')
            );
        }

        $gridField = GridField::create(
            'EnquiryFormFields',
            'Enquiry form fields',
            $this->EnquiryFormFields(),
            $config
        );
        $fields->addFieldToTab('Root.EnquiryForm', $gridField);

        if (!$this->CaptchaText) {
            $this->CaptchaText = 'Verification image';
        }

        $email_settings = [
            EmailField::create('EmailTo', 'Send email to')
                ->setAttribute('placeholder', 'you@yourdomain.com'),
            EmailField::create('EmailFrom', 'Send email from')
                ->setAttribute('placeholder', 'website@yourdomain.com')
                ->setRightTitle('For example website@yourdomain.com'),
            TextField::create('EmailSubject', 'Email subject'),
            HTMLEditorField::create(
                'EmailSubmitCompletion',
                'Message once completed'
            )
                ->setRows(10)
                ->addExtraClass('stacked'),
            EmailField::create('EmailBcc', 'BCC copy (optional)')
                ->setRightTitle(
                    'If you would like a copy of the enquiry to be sent elsewhere'
                ),
            TextField::create('EmailSubmitButtonText', 'Submit button text'),
            DropdownField::create(
                'EmailPlain',
                'Email format',
                [
                    0 => 'HTML email (default)',
                    1 => 'Plain text email',
                ]
            ),
            HeaderField::create('CaptchaHdr', 'Form captcha'),
            DropdownField::create(
                'AddCaptcha',
                'Captcha image',
                [
                    1 => 'Yes add a captcha image',
                    0 => 'No',
                ]
            )->setRightTitle(
                'Add an anti-spam "captcha" image. ' .
                'This adds a small image with 4 random ' .
                'numbers which needs to be filled in correctly.'
            ),
            TextField::create('CaptchaText', 'Field name'),
            TextField::create('CaptchaHelp', 'Captcha help')
                ->setRightTitle(
                    'Optionally add an explanation of what the captcha field is'
                ),
        ];

        $toggleSettings = ToggleCompositeField::create(
            'FormSettings',
            'Enquiry form settings',
            $email_settings
        );

        $fields->addFieldsToTab('Root.EnquiryForm', $toggleSettings);

        return $fields;
    }

    /**
     * Get the client IP by querying the $_SERVER array.
     *
     * @return string
     */
    public static function getClientIP()
    {
        $fields = Config::inst()->get(self::class, 'client_ip_fields');
        if ($fields) {
            if (is_string($fields)) {
                $fields = [$fields];
            }
            foreach ($fields as $field) {
                if (isset($_SERVER[$field])) {
                    return $_SERVER[$field];
                }
            }
        }

        return '';
    }

    /**
     * Return the hash to use for comparison.
     *
     * @param string $token Token
     *
     * @return string
     */
    public static function getHash($token)
    {
        $ip            = self::getClientIP();
        $random_string = Config::inst()->get(self::class, 'random_string');

        return md5(trim($token) . $ip . $random_string);
    }

    /**
     * Validate the current object.
     *
     * @see    {@link ValidationResult}
     *
     * @return ValidationResult
     */
    public function validate()
    {
        $valid = parent::validate();

        if ('' == $this->EmailSubmitButtonText) {
            $this->EmailSubmitButtonText = 'Submit enquiry';
        }

        if ('' == $this->CaptchaText) {
            $this->CaptchaText = 'Verification image';
        }

        return $valid;
    }

    /**
     * Get template data
     *
     * @param array $data Data array
     *
     * @return ArrayList
     */
    public function getTemplateData($data)
    {
        $emailData = ArrayList::create();
        foreach ($this->EnquiryFormFields() as $el) {
            $name = $el->FieldName;
            $key  = $el->formFieldName();
            $type = $el->FieldType;
            if (in_array($type, ['Header', 'HTML'])) {
                // Cosmetic element (not used in emails)
            } elseif (isset($data[$key]) && '' != $data[$key]) {
                // Ensure the element is valorized
                $raw = $data[$key];
                if (is_array($raw)) {
                    // Set of values
                    $value = ArrayList::create();
                    foreach ($raw as $item) {
                        $value->push(ArrayData::create(['Item' => $item]));
                    }
                } elseif (false === strpos($raw, "\n")) {
                    // Single line of text
                    $value = DBVarchar::create()->setValue($raw);
                } else {
                    // Multiple lines of text
                    $value = DBText::create()->setValue($raw);
                }
                $emailData->push(
                    ArrayData::create(
                        [
                            'Header' => $name,
                            'Value'  => $value,
                            'Type'   => $type,
                        ]
                    )
                );
            }
        }

        $templateData              = [];
        $templateData['EmailData'] = $emailData;

        return $templateData;
    }
}
