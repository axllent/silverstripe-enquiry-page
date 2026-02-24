<?php

namespace Axllent\EnquiryPage;

use Axllent\EnquiryPage\Model\EnquiryFormField;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\i18n\i18n;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBVarchar;
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
     * @var string|array
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
    private static $cms_icon_class = 'font-icon-p-post';

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
     * Static default values.
     *
     * @var array
     *
     * @config
     */
    private static $defaults = [
        'EmailPlain' => false,
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

    public static function fieldAddendum(string $field)
    {
        return i18n::getMessageProvider()->translate(
            __CLASS__.'.addendum_'.$field,
            "Addendum for $field",
            []
        );
    }

    /**
     * Get a field value or its localised default value
     *
     * @return string
     */
    public function fieldValueOrDefault(string $field)
    {
        $value = $this->getField($field);
        if ('' == $value) {
            $value = i18n::getMessageProvider()->translate(
                __CLASS__.'.default_'.$field,
                "Default for $field",
                []
            );
        }
        return $value;
    }

    /**
     * Dynamic default values.
     *
     * return static $this
     */
    public function populateDefaults()
    {
        $this->EmailSubject = $this->fieldValueOrDefault('EmailSubject');
        $this->CaptchaText = $this->fieldValueOrDefault('CaptchaText');
        $this->EmailSubmitButtonText = $this->fieldValueOrDefault('EmailSubmitButtonText');
        $this->EmailSubmitCompletion = $this->fieldValueOrDefault('EmailSubmitCompletion');
        return parent::populateDefaults();
    }

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

        $fields->removeByName(
            [
                'EnquiryFormFields',
                'EmailTo',
                'EmailFrom',
                'EmailSubject',
                'EmailSubmitCompletion',
                'EmailBcc',
                'EmailSubmitButtonText',
                'EmailPlain',
                'AddCaptcha',
                'CaptchaText',
                'CaptchaHelp',
            ]
        );

        $config = GridFieldConfig_RecordEditor::create(100);
        if (class_exists(GridFieldOrderableRows::class)) {
            $config->addComponent(
                GridFieldOrderableRows::create('SortOrder')
            );
        }

        $gridField = GridField::create(
            'EnquiryFormFields',
            $this->fieldLabel('EnquiryFormFields'),
            $this->EnquiryFormFields(),
            $config
        );
        $fields->addFieldToTab('Root.EnquiryForm', $gridField);

        // Localise the tab label
        $fields->fieldByName('Root.EnquiryForm')->setTitle(
            _t(__CLASS__.'.TabEnquiryForm', 'Enquiry form')
        );

        $this->CaptchaText = $this->fieldValueOrDefault('CaptchaText');

        $email_settings = [
            EmailField::create('EmailTo', $this->fieldLabel('EmailTo'))
                ->setAttribute('placeholder', 'you@yourdomain.com'),
            EmailField::create('EmailFrom', $this->fieldLabel('EmailFrom'))
                ->setAttribute('placeholder', 'website@yourdomain.com')
                ->setRightTitle(self::fieldAddendum('EmailFrom')),
            TextField::create('EmailSubject', $this->fieldLabel('EmailSubject')),
            HTMLEditorField::create(
                'EmailSubmitCompletion',
                $this->fieldLabel('EmailSubmitCompletion')
            )
                ->setRows(10)
                ->addExtraClass('stacked'),
            EmailField::create('EmailBcc', $this->fieldLabel('EmailBcc'))
                ->setRightTitle(self::fieldAddendum('EmailBcc')),
            TextField::create('EmailSubmitButtonText', $this->fieldLabel('EmailSubmitButtonText')),
            DropdownField::create(
                'EmailPlain',
                $this->fieldLabel('EmailPlain'),
                [
                    0 => _t(__CLASS__.'.FORMATHTML', 'HTML email (default)'),
                    1 => _t(__CLASS__.'.FORMATPLAIN', 'Plain text email'),
                ]
            ),
            HeaderField::create('CaptchaHdr', _t(__CLASS__.'.CAPTCHA', 'Form captcha')),
            DropdownField::create(
                'AddCaptcha',
                $this->fieldLabel('AddCaptcha'),
                [
                    1 => _t(__CLASS__.'.CAPTCHAENABLED', 'Yes, add a captcha image'),
                    0 => _t(__CLASS__.'.CAPTCHADISABLED', 'No'),
                ]
            )->setRightTitle(self::fieldAddendum('AddCaptcha')),
            TextField::create('CaptchaText', $this->fieldLabel('CaptchaText')),
            TextField::create('CaptchaHelp', $this->fieldLabel('CaptchaHelp'))
                ->setRightTitle(self::fieldAddendum('CaptchaHelp')),
        ];

        $toggleSettings = ToggleCompositeField::create(
            'FormSettings',
            _t(__CLASS__.'.TOGGLESETTINGS', 'Enquiry form settings'),
            $email_settings
        );

        $fields->addFieldToTab('Root.EnquiryForm', $toggleSettings);

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
     */
    public function validate(): ValidationResult
    {
        $valid = parent::validate();
        $this->EmailSubmitButtonText = $this->fieldValueOrDefault('EmailSubmitButtonText');
        $this->CaptchaText = $this->fieldValueOrDefault('CaptchaText');
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
