# Enquiry Page Module for Silverstripe

This is a simple module to add an Enquiry Page page type to the CMS. The module uses
optional vanilla JavaScript form validation. The enquiry form can be configured to add & order
your own fields, including the following types:

- **Text** (TextField/TextAreaField)
- **Email** (EmailField)
- **Select** (DropdownField), options configurable
- **Checkbox** (CheckboxSetField), options configurable
- **Options** (OptionsetField), options configurable
- **Readonly** (HTMLReadonlyField), optional html text
- **HTML section** (HTMLText)


## Configuration options include:

-   Send email to
-   Send email from (the "reply to" will default to the first Email field in the form, or alternatively this value)
-   Email subject
-   Message once completed
-   BCC copy
-   Submit button text
-   Optional built-in captcha image


## Captcha Image

A randomly-generated captcha image can be easily enabled in the form via the CMS. By
default it will produce a 4-digit image 60x30px with an input field next to it. If
you wish to change the height of the image (eg: to match bootstrap input styling), you
can configure this in your YAML:

```
Axllent\EnquiryPage\EnquiryPage:
  captcha_img_height: 35
```

Please note that the height should be no less than 20 (else the numbers may not be displayed properly).

If you use web services that dynamically change the `REMOTE_ADDR` field (most notably
CloudFlare) you can configure another field, e.g.:

```
Axllent\EnquiryPage\EnquiryPage:
  # Try $_SERVER['HTTP_CF_CONNECTING_IP'] (CloudFlare custom field) before
  # $_SERVER['REMOTE_ADDR'], so it will work with and without CloudFlare
  client_ip_fields:
    - HTTP_CF_CONNECTING_IP
    - REMOTE_ADDR
```

If required, you can disable the client IP retrieval entirely by unsetting that option.


## JavaScript validation

If you wish to turn on the built-in JavaScript validation, then this can be added to your
site's YAML config:

```
Axllent\EnquiryPage\EnquiryPage:
  js_validation: true
```


## Requirements

- Silverstripe ^6 (see other branches for previous versions)
- [symbiote/silverstripe-gridfieldextensions](https://github.com/symbiote/silverstripe-gridfieldextensions)


## Installation

```shell
composer require axllent/silverstripe-enquiry-page
```
