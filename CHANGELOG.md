# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

## [2.3.3] - 2021-06-18
- Added subscription mandate selection link to account update page.
- Fixed updating gateway in subscription/transaction on payment method update (via mandate selection URL).

## [2.3.2] - 2021-05-28
- Improved setting tax amount and rate in trial phase.

## [2.3.1] - 2021-05-11
- Use default gateway configuration setting.
- Reduced magic in MemberPress payment address transformation function.
- Improved tax calculation of payment from MemberPress subscription in trial (coupon code).

## [2.3.0] - 2021-04-26
- Added support for single-page checkout.

## [2.2.3] - 2021-02-08
- Fixed showing payment method specific input fields.

## [2.2.2] - 2021-01-18
- Added support for recurring payments with Apple Pay.
- Updated payment method icons to use wp-pay/logos library.

## [2.2.1] - 2021-01-14
- Updated packages.

## [2.2.0] - 2020-11-09
- Added Przelewy24 payment method.
- Added support for new subscription phases and periods.
- Added support for trials and (prorated) upgrades/downgrade.
- Set Pronamic Pay subscription on hold if non-recurring payment fails.

## [2.1.3] - 2020-08-05
- Fixed reactivating cancelled MemberPress subscription when pending recurring payment completes.

## [2.1.2] - 2020-04-20
- Fixed setting `complete` transaction status to `pending` again on free downgrade.

## [2.1.1] - 2020-04-03
- Fixed "PHP Warning: call_user_func() expects parameter 1 to be a valid callback".
- Updated integration dependencies.
- Set plugin integration name.

## [2.1.0] - 2020-03-19
- Extension extends abstract plugin integration.

## [2.0.13] - 2020-02-03
- Explicitly set transaction expiry date.

## [2.0.12] - 2019-12-22
- Improved error handling with exceptions.

## [2.0.11] - 2019-10-04
- Fixed showing lifetime columns on MemberPress subscriptions page if plugin is loaded before MemberPress.

## [2.0.10] - 2019-09-02
- Fix error "`DatePeriod::__construct()`: The recurrence count '0' is invalid. Needs to be > 0".

## [2.0.9] - 2019-08-26
- Updated packages.

## [2.0.8] - 2019-05-15
- Fix subscription source ID bug.
- Add more payment method icons.
- Add missing capabilities to Direct Debit Bancontact/iDEAL/Sofort gateways.

## [2.0.7] - 2019-02-04
- Fixed "Given country code not ISO 3166-1 alpha-2 value".

## [2.0.6] - 2019-01-24
- Fix fatal error due to Gateway class not found when processing status updates.

## [2.0.5] - 2019-01-21
- Added admin Pronamic subscription column to MemberPress subscriptions overview.
- Updated payment and subscription creation.

## [2.0.4] - 2018-12-12
- Add support for trials with the same length as subscription period.
- Improve upgrading/downgrading.

## [2.0.3] - 2018-09-14
- Added error message on registration form for failed payment.

## [2.0.2] - 2018-08-29
- Create a 'confirmed' 'subscription_confirmation' transaction for a grace period of 15 days.

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

[unreleased]: https://github.com/wp-pay-extensions/memberpress/compare/2.3.3...HEAD
[2.3.3]: https://github.com/wp-pay-extensions/memberpress/compare/2.3.2...2.3.3
[2.3.2]: https://github.com/wp-pay-extensions/memberpress/compare/2.3.1...2.3.2
[2.3.1]: https://github.com/wp-pay-extensions/memberpress/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/wp-pay-extensions/memberpress/compare/2.2.3...2.3.0
[2.2.3]: https://github.com/wp-pay-extensions/memberpress/compare/2.2.2...2.2.3
[2.2.2]: https://github.com/wp-pay-extensions/memberpress/compare/2.2.1...2.2.2
[2.2.1]: https://github.com/wp-pay-extensions/memberpress/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/wp-pay-extensions/memberpress/compare/2.1.3...2.2.0
[2.1.3]: https://github.com/wp-pay-extensions/memberpress/compare/2.1.2...2.1.3
[2.1.2]: https://github.com/wp-pay-extensions/memberpress/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/wp-pay-extensions/memberpress/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.13...2.1.0
[2.0.13]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.12...2.0.13
[2.0.12]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.11...2.0.12
[2.0.11]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.10...2.0.11
[2.0.10]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.9...2.0.10
[2.0.9]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.8...2.0.9
[2.0.8]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.7...2.0.8
[2.0.7]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.6...2.0.7
[2.0.6]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.5...2.0.6
[2.0.5]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/wp-pay-extensions/memberpress/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.5...2.0.0
[1.0.5]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.4...1.0.5
[1.0.4]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/wp-pay-extensions/memberpress/compare/1.0.0...1.0.1
