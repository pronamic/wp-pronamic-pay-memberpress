# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

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

[unreleased]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.2...HEAD
[1.0.2]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.0...1.0.1
