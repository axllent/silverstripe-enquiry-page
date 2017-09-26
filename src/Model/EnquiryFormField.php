<?php

namespace Axllent\EnquiryPage\Model;

use Axllent\EnquiryPage\EnquiryPage;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

class EnquiryFormField extends DataObject
{
    private static $table_name = 'EnquiryFormField';

    private static $default_sort = [
        '"SortOrder"' => 'ASC'
    ];

    private static $db = [
        'SortOrder' => 'Int',
        'FieldName' => 'Varchar(150)',
        'FieldType' => 'Enum("Text, Email, Select, Checkbox, Radio, Header, Note","Text")',
        'FieldOptions' => 'Text',
        'PlaceholderText' => 'Varchar(150)',
        'RequiredField' => 'Boolean',
    ];

    private static $fieldtypes = [
        'Text' => 'Text field',
        'Email' => 'Email field',
        'Select' => 'Select - Dropdown select field',
        'Checkbox' => 'Checkbox - multiple tick boxes',
        'Radio' => 'Radio - single tick option',
        'Header' => 'Header in the form',
        'Note' => 'Note in form'
    ];

    private static $has_one = [
        'EnquiryPage' => EnquiryPage::class
    ];

    private static $summary_fields = [
        'FieldName',
        'Type',
        'Required'
    ];

    private static $field_labels = [
        'FieldName' => 'Field name'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('SortOrder');
        $fields->removeByName('EnquiryPageID');

        $fields->addFieldToTab('Root.Main', DropdownField::create(
            'FieldType', 'Field type', self::$fieldtypes
        ));

        $fields->addFieldToTab('Root.Main', TextareaField::create('FieldOptions', 'Field options'));
        $fields->addFieldToTab('Root.Main', TextField::create('PlaceholderText', 'Placeholder text'));
        $fields->addFieldToTab('Root.Main', CheckboxField::create('RequiredField', 'Required field'));

        $hdrcnt = 0;

        switch ($this->FieldType) {
            case 'Select':
                $fields->addFieldToTab('Root.Main', HeaderField::create('FieldHdr_' . $hdrcnt++, 'Add select options below (one per line):', 4), 'FieldOptions');
                $fields->removeByName('PlaceholderText');
                break;
            case 'Checkbox':
                $fields->addFieldToTab('Root.Main', HeaderField::create('FieldHdr_' . $hdrcnt++, 'Add checkbox options below (one per line) - users can select multiple:', 4), 'FieldOptions');
                $fields->removeByName('RequiredField');
                $fields->removeByName('PlaceholderText');
                break;
            case 'Radio':
                $fields->addFieldToTab('Root.Main', HeaderField::create('FieldHdr_' . $hdrcnt++, 'Add options below (one per line) - users can select only one:', 4), 'FieldOptions');
                $fields->removeByName('PlaceholderText');
                break;
            case 'Header':
                $fields->removeByName('RequiredField');
                $fields->removeByName('FieldOptions');
                $fields->removeByName('PlaceholderText');
                $fields->addFieldsToTab('Root.Main', [
                    HeaderField::create('FieldOptionsInfo', 'Optional text below header.', 4),
                    TextareaField::create('FieldOptions', 'Text')
                ]);
                break;
            case 'Note':
                $fields->removeByName('RequiredField');
                $fields->removeByName('FieldOptions');
                $fields->addFieldsToTab('Root.Main', [
                    HeaderField::create('FieldOptionsInfo', 'If text is left empty then the "Field name" is used', 4),
                    TextareaField::create('FieldOptions', 'Text')
                ]);
                $fields->removeByName('PlaceholderText');
                break;
            case 'Text':
                $fields->removeByName('FieldOptions');
                $rows = NumericField::create('FieldOptions', 'Number of rows');
                $rows->value = 1;
                $fields->addFieldToTab('Root.Main', $rows, 'PlaceholderText');
                break;
            case 'Email':
                $fields->removeByName('FieldOptions');
                break;
            default:
                $fields->removeByName('FieldOptions');
                $fields->removeByName('PlaceholderText');
                break;
        }
        return $fields;
    }

    public function getType()
    {
        return self::$fieldtypes[$this->FieldType];
    }

    public function getRequired()
    {
        if (in_array($this->FieldType, ['Header', 'Note'])) {
            return false;
        }
        return $this->RequiredField ? 'Yes' : 'No';
    }

    public function validate()
    {
        $valid = parent::validate();
        if (trim($this->FieldName) == '') {
            $valid->error("Please enter a Field Name");
        }
        if (trim($this->FieldType) == '') {
            $valid->error("Please select a Field Type");
        }
        if ($this->FieldType == 'Text' && ($this->FieldOptions == '' || !is_numeric($this->FieldOptions) || $this->FieldOptions == 0)) {
            $this->FieldOptions = 1;
        }
        if ($this->FieldType == 'Select' || $this->FieldType == 'Checkbox') {
            $this->FieldOptions = trim(implode("\n", preg_split('/\n\r?/', $this->FieldOptions, -1, PREG_SPLIT_NO_EMPTY)));
        }
        return $valid;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->FieldName = trim($this->FieldName);
        if ($this->FieldType == 'Radio') {
            $this->PlaceholderText = '';
        } elseif (!in_array($this->FieldType, ['Text', 'Email', 'Select'])) {
            $this->RequiredField = 0;
            $this->PlaceholderText = '';
        }
    }

    public function getTitle()
    {
        if ($this->exists()) {
            return $this->FieldName;
        }
        return 'New';
    }

    /* Permissions */
    public function canView($member = null)
    {
        return true;
    }

    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
    	return Permission::check('SITETREE_EDIT_ALL', 'any', $member);
    }

    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member, $context);
        if ($extended !== null) {
            return $extended;
        }
    	return Permission::check('SITETREE_EDIT_ALL', 'any', $member);
    }

    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
    	return Permission::check('SITETREE_EDIT_ALL', 'any', $member);
    }
}
