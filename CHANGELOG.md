# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

## [4.8.0] - 2024-07-24

### Changed

- Send refund notices also for chargebacks. ([024dd78](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/024dd78b533b12c8183b8872cd4d3d2f2efb5cde))

Full set of changes: [`4.7.11...4.8.0`][4.8.0]

[4.8.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.11...v4.8.0

## [4.7.11] - 2023-12-18

### Fixed

- Fixed problem with taxes and coupon codes [#13](https://github.com/pronamic/wp-pronamic-pay-memberpress/issues/13) [#17](https://github.com/pronamic/wp-pronamic-pay-memberpress/issues/17)

Full set of changes: [`4.7.10...4.7.11`][4.7.11]

[4.7.11]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.10...v4.7.11

## [4.7.10] - 2023-07-18

### Commits

- Get invoice to get updated transaction total for trial. ([af62237](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/af62237830861ca59ad8713d143c74a1bda1e4aa))

Full set of changes: [`4.7.9...4.7.10`][4.7.10]

[4.7.10]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.9...v4.7.10

## [4.7.9] - 2023-07-12

### Commits

- Added support for in3 payment method. ([d8d7ef8](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/d8d7ef8d1197383e6b727d1e498b138084ad2681))

Full set of changes: [`4.7.8...4.7.9`][4.7.9]

[4.7.9]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.8...v4.7.9

## [4.7.8] - 2023-06-01

### Commits

- Switch from `pronamic/wp-deployer` to `pronamic/pronamic-cli`. ([b3577e0](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/b3577e035e66b47548d02c3b4b2aba9c1d50201d))
- Updated .gitattributes ([35fb2e0](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/35fb2e05b08fe9157d85dad25b2c3ff54ff9f240))

Full set of changes: [`4.7.7...4.7.8`][4.7.8]

[4.7.8]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.7...v4.7.8

## [4.7.7] - 2023-03-30

### Commits

- Fixed refunded amount check. ([7ac8c62](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/7ac8c62b3c77195203f588fab847ac2cbdf764f2))

### Composer

- Added `woocommerce/action-scheduler` `^3.4`.
Full set of changes: [`4.7.6...4.7.7`][4.7.7]

[4.7.7]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.6...v4.7.7

## [4.7.6] - 2023-03-29

### Commits

- Set Composer type to WordPress plugin. ([e963ad6](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/e963ad6107c7f6405b01e15bdb714132ccf5ab4a))
- Use new refunds API. ([a5422a3](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/a5422a3ca956a84b53d8762a3605ddc032ea51a1))
- Updated .gitattributes ([4486c56](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/4486c565d29ece75c555441a2cda3f52600fea73))

### Composer

- Changed `wp-pay/core` from `^4.6` to `v4.9.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.9.0
Full set of changes: [`4.7.5...4.7.6`][4.7.6]

[4.7.6]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.5...v4.7.6

## [4.7.5] - 2023-02-17

### Commits

- Fixed setting expiry date for trial period transaction. ([a56a0b9](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/a56a0b90a710f6cc4e4fbf520803135c4d7dee7b))

Full set of changes: [`4.7.4...4.7.5`][4.7.5]

[4.7.5]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.4...v4.7.5

## [4.7.4] - 2023-02-08
### Commits

- Don't send signup notices for recurring payments. ([8a574ea](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/8a574ea5a4342a5f660c5f2ead1271ae558fa9c5))

Full set of changes: [`4.7.3...4.7.4`][4.7.4]

[4.7.4]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.3...v4.7.4

## [4.7.3] - 2023-02-07
### Changed

- Upgrade `3.1.0` now runs asynchronously.
Full set of changes: [`4.7.2...4.7.3`][4.7.3]

[4.7.3]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.2...v4.7.3

## [4.7.2] - 2023-01-31
### Composer

- Changed `php` from `>=8.0` to `>=7.4`.
Full set of changes: [`4.7.1...4.7.2`][4.7.2]

[4.7.2]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.1...v4.7.2

## [4.7.1] - 2023-01-18

### Commits

- Trigger `pronamic_pay_payment_fulfilled` action if payment is completed. ([0449e17](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/0449e172e2952d5682b18ff40edfddbd4eb1bd5e))
- Happy 2023. ([515c4df](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/515c4df9d602b1d1deff17966f149d79cb76a70f))

Full set of changes: [`4.7.0...4.7.1`][4.7.1]

[4.7.1]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.7.0...v4.7.1

## [4.7.0] - 2022-12-23

### Commits

- Added "Requires Plugins" header. ([3a0c924](https://github.com/pronamic/wp-pronamic-pay-memberpress/commit/3a0c9241b862008bb28c52d5381d30565ffc3a44))

### Composer

- Changed `php` from `>=5.6.20` to `>=8.0`.
- Changed `wp-pay/core` from `^4.5` to `v4.6.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.6.0
Full set of changes: [`4.6.0...4.7.0`][4.7.0]

[4.7.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/v4.6.0...v4.7.0

## [4.6.0] - 2022-11-07
- Prevent recurring payment at gateways without recurring support. [#7](https://github.com/pronamic/wp-pronamic-pay-memberpress/pull/7)

## [4.5.1] - 2022-09-27
- Update to `wp-pay/core` version `^4.4`.

## [4.5.0] - 2022-09-26
- Updated for new payment methods and fields registration.

## [4.4.0] - 2022-07-01
### Added
- Added support for Klarna Pay Now, Klarna Pay Later and Klarna Pay Over Time ([pronamic/wp-pronamic-pay#190](https://github.com/pronamic/wp-pronamic-pay/issues/190)).

## [4.3.0] - 2022-05-30
### Added
- Added support for refunds and chargebacks (resolves [pronamic/wp-pronamic-pay#165](https://github.com/pronamic/wp-pronamic-pay/issues/165)).

## [4.2.0] - 2022-05-04
### Changed
- Update subscription phases on MemberPress subscription updates.
- Added subscription status and next payment date to MemberPress subscription form.
- Added payment status to MemberPress transaction form.

## [4.1.0] - 2022-04-11
- Call limit reached actions on subscription completion (fixes #2).

## [4.0.1] - 2022-02-16
- Fixed MemberPress gateway capabilities based on gateway support.
- Fixed confirming subscription confirmation transaction only for recurring payments.

## [4.0.0] - 2022-01-10
### Changed
- Updated to https://github.com/pronamic/wp-pay-core/releases/tag/4.0.0.

## [3.1.0] - 2021-09-03
- Completely revised integration.
- Improved support for free (amount = 0) transactions.
- Improved support for subscription upgrades and downgrades.
- Account page 'Update' allows users to manually pay for last period if payment failed.
- Added Pronamic payment column to the MemberPress transactions table in WordPress admin dashboard.
- Temporarily removed support for suspend and resume subscriptions due to unintended behavior.

## [3.0.3] - 2021-08-19
- Added Giropay gateway.

## [3.0.2] - 2021-08-16
- Fixed "Fatal error: Uncaught Error: Call to a member function get_periods() on bool".

## [3.0.1] - 2021-08-13
- Fixed "Fatal error: Uncaught Error: Class 'Pronamic\WordPress\Pay\Extensions\MemberPress\Money' not found".

## [3.0.0] - 2021-08-05
- Updated to `pronamic/wp-pay-core`  version `3.0.0`.
- Updated to `pronamic/wp-money`  version `2.0.0`.
- Changed `TaxedMoney` to `Money`, no tax info.
- Switched to `pronamic/wp-coding-standards`.
- Added subscription email parameters.

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

[unreleased]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/4.6.0...HEAD
[4.6.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/4.5.1...4.6.0
[4.5.1]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/4.5.0...4.5.1
[4.5.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/4.4.0...4.5.0
[4.4.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/4.3.0...4.4.0
[4.3.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/4.2.0...4.3.0
[4.2.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/4.1.0...4.2.0
[4.1.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/4.0.1...4.1.0
[4.0.1]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/3.1.0...4.0.0
[3.1.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/3.0.3...3.1.0
[3.0.3]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/3.0.2...3.0.3
[3.0.2]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.3.3...3.0.0
[2.3.3]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.3.2...2.3.3
[2.3.2]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.3.1...2.3.2
[2.3.1]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.2.3...2.3.0
[2.2.3]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.2.2...2.2.3
[2.2.2]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.2.1...2.2.2
[2.2.1]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.1.3...2.2.0
[2.1.3]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.1.2...2.1.3
[2.1.2]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.13...2.1.0
[2.0.13]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.12...2.0.13
[2.0.12]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.11...2.0.12
[2.0.11]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.10...2.0.11
[2.0.10]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.9...2.0.10
[2.0.9]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.8...2.0.9
[2.0.8]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.7...2.0.8
[2.0.7]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.6...2.0.7
[2.0.6]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.5...2.0.6
[2.0.5]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/1.0.5...2.0.0
[1.0.5]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/1.0.4...1.0.5
[1.0.4]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/pronamic/wp-pronamic-pay-memberpress/compare/1.0.0...1.0.1
