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

### Memberships Matrix

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

## Links

*	[MemberPress](https://www.memberpress.com/)
