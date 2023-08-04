# Changelog

Notable changes to this project will be documented in this file.

## [3.0.0]

- Release for Silverstripe 5
- Use Silverstripe icon for page icon
- Switch from `undefinedoffset/sortablegridfield` to `symbiote/silverstripe-gridfieldextensions` for sorting


## [2.3.4]

- Change `$default_sort` to a string


## [2.2.3]

- Fix `Email::is_valid_address()` error when value is null


## [2.2.2]

- Add new method to generate form field names
- Add `onBeforeSendEnquiryForm` & `onAfterSendEnquiryForm` extension hooks


## [2.2.1]

- Fix HTML escaping for `CaptchaHelp`


## [2.2.0]

- Merge PR from @ntd to customise the client IP environment header
- Major code cleanup


## [2.1.0]

- Merge PR from @ntd to add required checkbox, custom html, plain text email option
- Separate plaintext email template
- Fix validation error (`addError()`)
- HTML text for Readonly & HTML content (supports internal linking)
- Remove 'Note' (Readonly now does the same)


## [2.0.5]

- Fixes for upcoming $icon changes


## [2.0.4]

- Switch to silverstripe-vendormodule


## [2.0.3]

- Fix EnquiryFormField permissions


## [2.0.2]

- Change static variables to private


## [2.0.1]

- New Session methods to support SilverStripe 4.0.0-beta1


## [2.0.0]

- Rewrite for SilverStripe 4
- Namespacing


## [1.1.5]

- Add yaml configuration to disable built-in JS validation
- Improved JS email validation


## [1.1.4]

- Use FileNameFilter for $_GET parameters


## [1.1.3]

- Allow fields to be set with $_GET values


## [1.1.2]

- Use SS template for CaptchaField


## [1.1.1]

- Remove max-length property (invalid html for number field)


## [1.1.0]

- Change to captcha input type to `number`
- Update field creation syntax for `Field::create()` rather than new `Field()`
- Use `->setRightTitle()` instead of LiteralFields


## [1.0.1]

- Add autocomplete=off for validation image
- Reset value on incorrect submission for validation image


## [1.0.0]

- Adopt semantic versioning releases
- Release versions
