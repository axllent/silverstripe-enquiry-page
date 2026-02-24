<?php

namespace Axllent\EnquiryPage\Model;

use Axllent\EnquiryPage\EnquiryPage;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\i18n\i18n;
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
     * @var array
     *
     * @config
     */
    private static $default_sort = '"SortOrder" ASC';

    /**
     * Database field definitions.
     *
     * @var array
     *
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
     * One-to-zero relationship definitions.
     *
     * @var array
     *
     * @config
     */
    private static $has_one = [
        'EnquiryPage' => EnquiryPage::class,
    ];

    /**
     * Provides a default list of fields to be used by a 'summary'
     * view of this object.
     *
     * @var string
     *
     * @config
     */
    private static $summary_fields = [
        'FieldName',
        'Type',
        'Required',
    ];

    /**
     * Try to localise the summary fields before returning them.
     *
     * @return array
     */
    public function summaryFields()
    {
        $fields = parent::summaryFields();
        foreach ($fields as $field => &$label) {
            $label = i18n::getMessageProvider()->translate(
                __CLASS__.'.summary_'.$field,
                $label,
                []
            );
        }
        return $fields;
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
        $fields->removeByName('SortOrder');
        $fields->removeByName('EnquiryPageID');

        $fields->insertBefore(
            'FieldName',
            DropdownField::create(
                'FieldType',
                $this->fieldLabel('FieldType'),
                self::$field_types
            )
        );

        $fields->addFieldsToTab(
            'Root.Main',
            [
                TextareaField::create('FieldOptions', $this->fieldLabel('FieldOptions')),
                TextField::create('PlaceholderText', $this->fieldLabel('PlaceholderText')),
                CheckboxField::create('RequiredField', $this->fieldLabel('RequiredField')),
            ]
        );

        $hdrcnt = 0;

        switch ($this->FieldType) {
            case 'Select':
                $fields->addFieldToTab(
                    'Root.Main',
                    HeaderField::create(
                        'FieldHdr_' . $hdrcnt++,
                        _t(__CLASS__.'.SELECTOPTIONS', 'Add options below (one per line):'),
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
                        _t(__CLASS__.'.CHECKBOXOPTIONS', 'Add options below (one per line) - users can select multiple:'),
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
                        _t(__CLASS__.'.RADIOOPTIONS', 'Add options below (one per line) - users can select only one:'),
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
                    HTMLEditorField::create(
                        'FieldOptions',
                        _t(__CLASS__.'.HEADEROPTIONS', 'HTML text')
                    )
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
                    HTMLEditorField::create(
                        'FieldOptions',
                        _t(__CLASS__.'.HTMLOPTIONS', 'HTML content')
                    )
                );

                break;

            case 'Text':
                $fields->removeByName('FieldOptions');
                $fields->addFieldToTab(
                    'Root.Main',
                    NumericField::create(
                        'FieldOptions',
                        _t(__CLASS__.'.TEXTOPTIONS', 'Number of rows')
                    )
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
        return isset(self::$field_types[$this->FieldType])
        ? self::$field_types[$this->FieldType] : 'Invalid';
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

        return $this->dbObject('RequiredField')->Nice();
    }

    /**
     * Generate a unique form field name
     *
     * @return string
     */
    public function formFieldName()
    {
        return preg_replace(
            '/[^a-z0-9]/i',
            '',
            $this->FieldName
        ) . '_' . $this->ID;
    }

    /**
     * Validate the current object.
     *
     * @see    {@link ValidationResult}
     */
    public function validate(): ValidationResult
    {
        $valid = parent::validate();

        $this->FieldName = trim((string) $this->FieldName);
        $this->FiledType = trim((string) $this->FieldType);

        if ('HTML' == $this->FieldType) {
            $this->FieldName = 'HTML';
        }
        if ('' == $this->FieldName) {
            $valid->addError(_t(__CLASS__.'.ERRORNONAME', 'Please enter a Field Name'));
        }
        if ('' == $this->FieldType) {
            $valid->addError(_t(__CLASS__.'.ERRORNOTYPE', 'Please select a Field Type'));
        }
        if ('Text' == $this->FieldType
            && (
                '' == $this->FieldOptions
                || !is_numeric($this->FieldOptions)
                || 0 == $this->FieldOptions
            )
        ) {
            $this->FieldOptions = 1;
        }
        if ('Select' == $this->FieldType || 'Checkbox' == $this->FieldType) {
            $this->FieldOptions = trim(
                implode(
                    "\n",
                    preg_split(
                        '/\n\r?/',
                        (string) $this->FieldOptions,
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

        if (!$this->SortOrder) {
            $this->SortOrder = self::get()->max('SortOrder') + 1;
        }

        if ('Radio' == $this->FieldType) {
            $this->PlaceholderText = '';
        } elseif (!in_array($this->FieldType, ['Text', 'Email', 'Select', 'Checkbox'])) {
            $this->RequiredField   = 0;
            $this->PlaceholderText = '';
        }
    }

    /**
     * Get title
     */
    public function getTitle(): string
    {
        return $this->exists() ? $this->FieldName : 'New';
    }

    /**
     * Permissions canView
     *
     * @param Member $member SilverStripe member
     *
     * @return bool
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
     * @return bool
     */
    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
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
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member, $context);
        if (null !== $extended) {
            return $extended;
        }

        return Permission::check('SITETREE_EDIT_ALL', 'any', $member);
    }

    /**
     * Permissions canDelete
     *
     * @param Member $member SilverStripe member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        return Permission::check('SITETREE_EDIT_ALL', 'any', $member);
    }
}
