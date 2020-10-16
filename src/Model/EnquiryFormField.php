<?php
namespace Axllent\EnquiryPage\Model;

use Axllent\EnquiryPage\EnquiryPage;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

class EnquiryFormField extends DataObject
{
    /**
     * Table name
     *
     * @var string
     */
    private static $table_name = 'EnquiryFormField';

    /**
     * The default sort.
     *
     * @var    array
     * @config
     */
    private static $default_sort = [
        '"SortOrder"' => 'ASC',
    ];

    /**
     * Database field definitions.
     *
     * @var    array
     * @config
     */
    private static $db = [
        'SortOrder'       => 'Int',
        'FieldName'       => 'Varchar(150)',
        'FieldType'       => 'Enum("Text, Email, Select, Checkbox, Radio, Header, HTML", "Text")',
        'FieldOptions'    => 'HTMLText',
        'PlaceholderText' => 'Varchar(150)',
        'RequiredField'   => 'Boolean',
    ];

    /**
     * Field types
     *
     * @var array
     */
    private static $field_types = [
        'Text'     => 'Text field',
        'Email'    => 'Email field',
        'Select'   => 'Select - dropdown select field',
        'Checkbox' => 'Checkbox - multiple tick boxes',
        'Radio'    => 'Radio - single tick option',
        'Header'   => 'Readonly field',
        'HTML'     => 'HTML section',
    ];

    /**
     * One-to-zero relationship defintions.
     *
     * @var    array
     * @config
     */
    private static $has_one = [
        'EnquiryPage' => EnquiryPage::class,
    ];

    /**
     * Provides a default list of fields to be used by a 'summary'
     * view of this object.
     *
     * @var    string
     * @config
     */
    private static $summary_fields = [
        'FieldName',
        'Type',
        'Required',
    ];

    /**
     * Field labels
     *
     * @var    string
     * @config
     */
    private static $field_labels = [
        'FieldName' => 'Field name',
    ];

    /**
     * Data administration interface in Silverstripe.
     *
     * @see    {@link ValidationResult}
     * @return FieldList Returns a TabSet for usage within the CMS
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('SortOrder');
        $fields->removeByName('EnquiryPageID');

        $fields->addFieldToTab(
            'Root.Main',
            DropdownField::create(
                'FieldType',
                'Field type',
                self::$field_types
            )
        );

        $fields->addFieldsToTab(
            'Root.Main',
            [
                TextareaField::create('FieldOptions', 'Field options'),
                TextField::create('PlaceholderText', 'Placeholder text'),
                CheckboxField::create('RequiredField', 'Required field'),
            ]
        );

        $hdrcnt = 0;

        switch ($this->FieldType) {
            case 'Select':
                $fields->addFieldToTab(
                    'Root.Main',
                    HeaderField::create(
                        'FieldHdr_' . $hdrcnt++,
                        'Add select options below (one per line):',
                        4
                    ),
                    'FieldOptions'
                );
                $fields->removeByName('PlaceholderText');
                break;
            case 'Checkbox':
                $fields->addFieldToTab(
                    'Root.Main',
                    HeaderField::create(
                        'FieldHdr_' . $hdrcnt++,
                        'Add checkbox options below (one per line) - users can select multiple:',
                        4
                    ),
                    'FieldOptions'
                );
                $fields->removeByName('PlaceholderText');
                break;
            case 'Radio':
                $fields->addFieldToTab(
                    'Root.Main',
                    HeaderField::create(
                        'FieldHdr_' . $hdrcnt++,
                        'Add options below (one per line) - users can select only one:',
                        4
                    ),
                    'FieldOptions'
                );
                $fields->removeByName('PlaceholderText');
                break;
            case 'Header':
                // Readonly form field
                $fields->removeByName(
                    [
                        'RequiredField',
                        'FieldOptions',
                        'PlaceholderText',
                    ]
                );

                $fields->addFieldToTab(
                    'Root.Main',
                    HtmlEditorField::create('FieldOptions', 'HTML text')
                );
                break;
            case 'HTML':
                $fields->removeByName(
                    [
                        'RequiredField',
                        'FieldOptions',
                        'FieldName',
                        'PlaceholderText',
                    ]
                );

                $fields->addFieldToTab(
                    'Root.Main',
                    HtmlEditorField::create('FieldOptions', 'HTML content')
                );
                break;
            case 'Text':
                $fields->removeByName('FieldOptions');
                $fields->addFieldToTab(
                    'Root.Main',
                    NumericField::create('FieldOptions', 'Number of rows')
                        ->setValue(1),
                    'PlaceholderText'
                );
                break;
            case 'Email':
                $fields->removeByName('FieldOptions');
                break;
            default:
                $fields->removeByName(
                    [
                        'FieldOptions',
                        'PlaceholderText',
                    ]
                );
                break;
        }

        return $fields;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return isset(self::$field_types[$this->FieldType]) ?
        self::$field_types[$this->FieldType] : 'Invalid';
    }

    /**
     * Get required
     *
     * @return mixed
     */
    public function getRequired()
    {
        if (in_array($this->FieldType, ['Header', 'HTML'])) {
            return false;
        }

        return $this->RequiredField ? 'Yes' : 'No';
    }

    /**
     * Validate the current object.
     *
     * @see    {@link ValidationResult}
     * @return ValidationResult
     */
    public function validate()
    {
        $valid = parent::validate();

        if ($this->FieldType == 'HTML') {
            $this->FieldName = 'HTML Content';
        }
        if (trim($this->FieldName) == '') {
            $valid->addError('Please enter a Field Name');
        }
        if (trim($this->FieldType) == '') {
            $valid->addError('Please select a Field Type');
        }
        if ($this->FieldType == 'Text'
            && ($this->FieldOptions == ''
                || !is_numeric($this->FieldOptions)
                || $this->FieldOptions == 0
            )
        ) {
            $this->FieldOptions = 1;
        }
        if ($this->FieldType == 'Select' || $this->FieldType == 'Checkbox') {
            $this->FieldOptions = trim(
                implode(
                    "\n",
                    preg_split(
                        '/\n\r?/',
                        $this->FieldOptions,
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    )
                )
            );
        }

        return $valid;
    }

    /**
     * Event handler called before writing to the database.
     *
     * @return void
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->FieldName = trim($this->FieldName);
        if ($this->FieldType == 'Radio') {
            $this->PlaceholderText = '';
        } elseif (!in_array($this->FieldType, ['Text', 'Email', 'Select', 'Checkbox'])) {
            $this->RequiredField   = 0;
            $this->PlaceholderText = '';
        }
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        if ($this->exists()) {
            return $this->FieldName;
        }

        return 'New';
    }

    /**
     * Permissions canView
     *
     * @param Member $member SilverStripe member
     *
     * @return boolean
     */
    public function canView($member = null)
    {
        return true;
    }

    /**
     * Permissions canEdit
     *
     * @param Member $member SilverStripe member
     *
     * @return boolean
     */
    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::check('SITETREE_EDIT_ALL', 'any', $member);
    }

    /**
     * Permissions canCreate
     *
     * @param Member $member  SilverStripe member
     * @param array  $context Array
     *
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member, $context);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::check('SITETREE_EDIT_ALL', 'any', $member);
    }

    /**
     * Permissions canDelete
     *
     * @param Member $member SilverStripe member
     *
     * @return boolean
     */
    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::check('SITETREE_EDIT_ALL', 'any', $member);
    }
}
