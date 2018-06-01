# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

## [2.0.1] - 2018-06-01
- Improved return URL's support.
- Added subscription source URL filter.

## [2.0.0] - 2018-05-14
- Switched to PHP namespaces.

## [1.0.5] - 2017-12-12
- Added Pronamic gateway.
- Fixed MemberPress v1.3.18 redirect URL compatibility.
- Added Bitcoin and PayPal gateways.
- Updated iDEAL and PayPal icons.

## [1.0.4] - 2017-01-25
- Use MeprUtils class for sending transaction notices (Zendesk #10084).
- No longer echo invoice in payment redirect function.
- Added filter payment source description and URL.
- Use credit card alias instead of Sofort in credit card gateway.

## [1.0.3] - 2016-10-20
- Added membership slug to thank you page URL.
- Maybe cancel old subscriptions and send notices.
- Make use of new Bancontact label and constant.
- Use MemberPress transaction number in 'Thank you' redirect instead of payment source ID.

## [1.0.2] - 2016-06-08
- Added support for gateway input fields.
- Added a iDEAL icon to the iDEAL gateway.
- Only use MeprTransaction object in payment data constructor, remove unused variable `$product`

## [1.0.1] - 2016-04-13
- Implemented new redirect system.
- No longer use camelCase for payment data.
- Redirect to payment form action if payment was unsuccessful.
- Fixed number of arguments passed to send_product_welcome_notices().

## 1.0.0 - 2016-02-01
- First release.

[unreleased]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.1...HEAD
[2.0.1]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.5...2.0.0
[1.0.5]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.4...1.0.5
[1.0.4]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.0...1.0.1
