<?php

class EnquiryFormField extends DataObject {

	public static $default_sort = "SortOrder ASC";

	private static $db = array(
		'SortOrder' => 'Int',
		'FieldName' => 'Varchar(150)',
		'FieldType' => 'Enum("Text, Email, Select, Checkbox, Radio, Header, Note","Text")',
		'FieldOptions' => 'Text',
		'PlaceholderText' => 'Varchar(150)',
		'RequiredField' => 'Boolean',
	);

	private static $defaults = array(
		'SortOrder' => 99
	);

	public static $fieldtypes = array(
		'Text' => 'Text field',
		'Email' => 'Email field',
		'Select' => 'Select - Dropdown select field',
		'Checkbox' => 'Checkbox - multiple tick boxes',
		'Radio' => 'Radio - single tick option',
		'Header' => 'Header in the form',
		'Note' => 'Note in form'
	);

	private static $has_one = array('EnquiryPage' => 'SiteTree');

	public static $summary_fields = array('FieldName', 'Type', 'Required');

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName('SortOrder');
		$fields->removeByName('EnquiryPageID');

		$fields->addFieldToTab('Root.Main', new DropdownField(
			'FieldType', 'Field Type', self::$fieldtypes
		));

		$fields->addFieldToTab('Root.Main', new TextareaField('FieldOptions', 'Field Options'));
		$fields->addFieldToTab('Root.Main', new TextField('PlaceholderText', 'Placeholder Text'));
		$fields->addFieldToTab('Root.Main', new CheckboxField('RequiredField', 'Required Field'));

		switch($this->FieldType) {
			case 'Select':
				$fields->addFieldToTab('Root.Main', new HeaderField('Add select options below (one per line):', 4), 'FieldOptions');
				$fields->removeByName('PlaceholderText');
				break;
			case 'Checkbox':
				$fields->addFieldToTab('Root.Main', new HeaderField('Add checkbox options below (one per line) - users can select multiple:', 4), 'FieldOptions');
				$fields->removeByName('RequiredField');
				$fields->removeByName('PlaceholderText');
				break;
			case 'Radio':
				$fields->addFieldToTab('Root.Main', new HeaderField('Add options below (one per line) - users can select only one:', 4), 'FieldOptions');
				$fields->removeByName('PlaceholderText');
				break;
			case 'Header':
				$fields->removeByName('RequiredField');
				$fields->removeByName('FieldOptions');
				$fields->removeByName('PlaceholderText');
				$fields->addFieldsToTab('Root.Main', array(
					new HeaderField('FieldOptionsInfo', 'Optional text below header.', 4),
					new TextareaField('FieldOptions', 'Text')
				));
				break;
			case 'Note':
				$fields->removeByName('RequiredField');
				$fields->removeByName('FieldOptions');
				$fields->addFieldsToTab('Root.Main', array(
					new HeaderField('FieldOptionsInfo', 'If text is left empty then the Field Name is used', 4),
					new TextareaField('FieldOptions', 'Text')
				));
				$fields->removeByName('PlaceholderText');
				break;
			case 'Text':
				$fields->removeByName('FieldOptions');
				$rows = new NumericField('FieldOptions', 'Number of rows');
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
		if (!$this->exists()) {
			$fields->removeByName('RequiredField');
		}
		return $fields;
	}

	public function getType() {
		return self::$fieldtypes[$this->FieldType];
	}

	public function getRequired() {
		if (in_array($this->FieldType, array('Header', 'Note')))
			return false;
		return $this->RequiredField ? 'Yes' : 'No';
	}

	public function validate() {
		$valid = parent::validate();
		if (trim($this->FieldName) == '')
			$valid->error("Please enter a Field Name");
		if(trim($this->FieldType) == '')
			$valid->error("Please select a Field Type");
		if ($this->FieldType == 'Text' && ($this->FieldOptions == '' || !is_numeric($this->FieldOptions) || $this->FieldOptions == 0))
			$this->FieldOptions = 1;
		if ($this->FieldType == 'Select' || $this->FieldType == 'Checkbox'){
			$this->FieldOptions = trim(implode("\n", preg_split('/\n\r?/', $this->FieldOptions, -1, PREG_SPLIT_NO_EMPTY)));
		}
		return $valid;
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->FieldName = trim($this->FieldName);
		if (in_array($this->FieldType, array('Radio'))) {
			$this->PlaceholderText = '';
		}
		else if(!in_array($this->FieldType, array('Text', 'Email', 'Select'))) {
			$this->RequiredField = 0;
			$this->PlaceholderText = '';
		}

	}

	/* Permissions */
	function canView($member = null) {
		return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
	}
	function canEdit($member = null) {
		return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
	}
	function canDelete($member = null) {
		return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
	}
	function canCreate($member = null) {
		return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
	}

	function getTitle() {
		if($this->exists()) return $this->FieldName;
		return 'New';
	}

}