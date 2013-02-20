<?php

class EnquiryFormField extends DataObject {

	public static $default_sort = "SortOrder ASC";

	public static $db = array(
		'SortOrder' => 'Int',
		'FieldName' => 'Varchar(150)',
		'FieldType' => 'Enum("Text, Email, Select, Checkbox, Header, Note","Text")',
		'FieldOptions' => 'Text',
		'PlaceholderText' => 'Varchar(150)',
		'RequiredField' => 'Boolean',

	);
	public static $defaults = array(
		'SortOrder' => 99
	);

	public static $has_one = array('EnquiryPage' => 'EnquiryPage');

	public static $summary_fields = array('FieldName', 'FieldType');

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName('SortOrder');
		$fields->removeByName('EnquiryFormID');
		switch($this->FieldType) {
			case 'Select':
				$fields->addFieldToTab('Root.Main', new HeaderField('Add select options below (one per line):', 4), 'FieldOptions');
				$fields->removeByName('PlaceholderText');
				break;
			case 'Checkbox':
				$fields->addFieldToTab('Root.Main', new HeaderField('Add checkbox options below (one per line):', 4), 'FieldOptions');
				$fields->removeByName('RequiredField');
				$fields->removeByName('PlaceholderText');
				break;
			case 'Header':
				$fields->removeByName('RequiredField');
				$fields->removeByName('FieldOptions');
				$fields->removeByName('PlaceholderText');
				break;
			case 'Note':
				$fields->removeByName('RequiredField');
				$fields->removeByName('FieldOptions');
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

	public function validate(){
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
		if(!in_array($this->FieldType, array('Text', 'Email', 'Select'))) {
			$this->RequiredField = 0;
			$this->PlaceholderText = '';
		}

	}

	function getTitle(){
		if($this->exists()) return $this->FieldName;
		return 'New';
	}

}