Enquiry Page Module for SilverStripe 3
======================================
This is a simple module to add an Enquiry Page pagetype to the CMS.
The module uses vanilla JavaScript, so no requirements for third-party JavaScript libraries
(ie: jQuery or MooTools). The enquiry form can be configured to add &
order your own fields, including the following types:

* **Text** (input / textarea), required or not
* **Email** (input), required or not
* **Select** (select field), options configurable, required or not
* **Checkbox** (checkbox), options configurable
* **Header** (h4), section header
* **Note** (paragraph of text)

## Configuration option include:
* Send email to
* Send email from (the "reply to" will default to the first Email field in the form, or alternatively this value)
* Email subject
* Message once completed
* BCC messages
* Submit button text
* Optional built-in captcha image (anti-spam)

## Requirements
* SilverStripe >=3.1
* [SortableGridField](https://github.com/UndefinedOffset/SortableGridField)