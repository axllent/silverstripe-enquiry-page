# Enquiry Page Module for SilverStripe 4

This is a simple module to add an Enquiry Page pagetype to the CMS. The module uses
optional JavaScript form validation, so no requirements for third-party JavaScript
libraries (ie: jQuery or MooTools). The enquiry form can be configured to add & order
your own fields, including the following types:

- **Text** (TextField/TextAreaField)
- **Email** (TextField)
- **Select** (DropdownField), options configurable
- **Checkbox** (CheckboxSetField), options configurable
- **Options** (OptionsetField), options configurable
- **Readonly** (HTMLReadonlyField), optional html text
- **HTML section** (HTMLText)


## Configuration options include:

-   Send email to
-   Send email from (the "reply to" will default to the first Email field in the form,
    or alternatively this value)
-   Email subject
-   Message once completed
-   BCC copy
-   Submit button text
-   Optional built-in captcha image


## Captcha Image

A randomly-generated captcha image can be easily enabled in the form via the CMS. By
default it will produce a 4-digit image 60x30px with an input field next to it. If
you wish to change the height of the image (eg: to match boostrap input styling), you
can configure this in your YAML:

```
Axllent\EnquiryPage\EnquiryPage:
  captcha_img_height: 35
```

Please note that the height should be no less than 20 (else the numbers may not be displayed properly).


## JavaScript validation

If you wish to turn on the built-in JavaScript validation, then this can be added to your
site's YAML config:

```
Axllent\EnquiryPage\EnquiryPage:
  js_validation: true
```


## Requirements

- SilverStripe ^4
- [SortableGridField](https://github.com/UndefinedOffset/SortableGridField)


## Installation

```shell
composer require axllent/silverstripe-enquiry-page
```
