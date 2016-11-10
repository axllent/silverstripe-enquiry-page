# Enquiry Page Module for SilverStripe 3

This is a simple module to add an Enquiry Page pagetype to the CMS. The module uses optional JavaScript form validation,
so no requirements for third-party JavaScript libraries (ie: jQuery or MooTools). The enquiry form can be configured to
add & order your own fields, including the following types:

- **Text** (input / textarea), required or not
- **Email** (input), required or not
- **Select** (select field), options configurable, required or not
- **Checkbox** (checkbox), options configurable
- **Header** (h4), section header
- **Note** (paragraph of text)

## Configuration options include:

- Send email to
- Send email from (the "reply to" will default to the first Email field in the form, or alternatively this value)
- Email subject
- Message once completed
- BCC messages
- Submit button text
- Optional built-in captcha image (anti-spam)

## Disable JavaScript validation
If you wisht o turn off the built-in JavaScript validation, then this can be added to your site's YAML config:

```
EnquiryPage:
  js_validation: false
```

## Requirements

- SilverStripe >=3.1
- [SortableGridField](https://github.com/UndefinedOffset/SortableGridField)
