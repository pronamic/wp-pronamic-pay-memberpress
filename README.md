# WordPress Pay Extension: MemberPress

**MemberPress driver for the WordPress payment processing library.**

[![PHP from Packagist](https://img.shields.io/packagist/php-v/wp-pay-extensions/memberpress.svg)](https://packagist.org/packages/wp-pay-extensions/memberpress)
[![Build Status](https://travis-ci.org/wp-pay-extensions/memberpress.svg?branch=develop)](https://travis-ci.org/wp-pay-extensions/memberpress)
[![Built with Grunt](https://cdn.gruntjs.com/builtwith.svg)](http://gruntjs.com/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/wp-pay-extensions/memberpress/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/wp-pay-extensions/memberpress/?branch=develop)
[![Code Coverage](https://scrutinizer-ci.com/g/wp-pay-extensions/memberpress/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/pronamic/wp-pronamic-ideal/?branch=develop)
[![Build Status](https://scrutinizer-ci.com/g/wp-pay-extensions/memberpress/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/pronamic/wp-pronamic-ideal/build-status/develop)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/wp-pay-extensions/memberpress/badges/code-intelligence.svg?b=develop)](https://scrutinizer-ci.com/code-intelligence)
[![codecov](https://codecov.io/gh/wp-pay-extensions/memberpress/branch/develop/graph/badge.svg)](https://codecov.io/gh/wp-pay-extensions/memberpress)

## Tests

### Memberships

#### One-Time - Lifetime

| Field                 | Value        |
| --------------------- | ------------ |
| Billing Type          | One-Time     |
| Access                | Lifetime     |

#### One-Time - Expire

| Field                 | Value        |
| --------------------- | ------------ |
| Billing Type          | One-Time     |
| Access                | Lifetime     |

#### One-Time - Fixed Expire

| Field                 | Value        |
| --------------------- | ------------ |
| Billing Type          | One-Time     |
| Access                | Fixed Expire |

#### Recurring - Monthly

| Field                 | Value        |
| --------------------- | ------------ |
| Billing Type          | Recurring    |
| Interval              | Monthly      |

#### Recurring - Monthly - Trial Period - 10 days - € 50,00

| Field                 | Value        |
| --------------------- | ------------ |
| Billing Type          | Recurring    |
| Interval              | Monthly      |
| Trial Period          | ☑️           |
| Trial Duration (Days) | 10           |
| Trial Amount (€)      | 50,00        |

#### Recurring - Monthly - Trial Period - 10 days - € 0,00

| Field                 | Value        |
| --------------------- | ------------ |
| Billing Type          | Recurring    |
| Interval              | Monthly      |
| Trial Period          | ☑️           |
| Trial Duration (Days) | 10           |
| Trial Amount (€)      | 0,00         |

#### Recurring - Monthly - Trial Period - 10 days - € 50,00 - Allow Only One Trial

| Field                 | Value        |
| --------------------- | ------------ |
| Billing Type          | Recurring    |
| Interval              | Monthly      |
| Trial Period          | ☑️           |
| Trial Duration (Days) | 10           |
| Trial Amount (€)      | 0,00         |
| Allow Only One Trial  | ☑️           |

### Groups

#### Group - Upgrade Path - One-Time - Recurring

| Field                 | Value        |
| --------------------- | ------------ |
| Upgrade Path          | ☑️           |
| Memberships           | 1. One-Time  |
|                       | 2. Recurring |

#### Group - Upgrade Path - Recurring - One-Time

| Field                 | Value        |
| --------------------- | ------------ |
| Upgrade Path          | ☑️           |
| Memberships           | 1. Recurring |
|                       | 2. One-Time  |

### Discounts

#### Discount - 100% - Standard

| Field                 | Value        |
| --------------------- | ------------ |
| Discount              | 100%         |
| Discount Mode         | Standard     |

#### Discount - 0% - First Payment - 100%

| Field                  | Value         |
| ---------------------- | ------------- |
| Discount               | 0%.           |
| Discount Mode          | First Payment |
| First Payment Discount | 100%          |

### Settings

#### Account » Permissions

☑️ Allow Members to Cancel their own subscriptions

☑️ Allow Members to Pause & Resume their own subscriptions

> This option will only be available if this is enabled and the user purchased their subsciption using PayPal or Stripe.

#### Account » Registration

☑️ Disable the 1 day grace period after signup 

> PayPal, Stripe, and Authorize.net can sometimes take up to 24 hours to process the first payment on a members recurring subscription. By default MemberPress allows a 1 day grace period after a member signs up, so they can access the site immediately rather than wait for their payment to clear.
>
> If you would like to make them wait for the payment to clear before they are allowed to access the site, then enable this option.

☑️ Enable Single Page Checkout

> Enabling this will eliminate the second step of the checkout process. Users will be able to enter their personal and payment details during the first step instead.

☑️ Pro-rate subscription prices when a member upgrades

☑️ Enable Single Page Checkout Invoice

> Enabling this will display Invoice table above the payment options.

## Links

*	[MemberPress](https://www.memberpress.com/)
