[![Latest Version on Packagist](https://img.shields.io/packagist/v/codegreencreative/laravel-aweber.svg?style=flat-square)](https://packagist.org/packages/codegreencreative/laravel-aweber)
[![Total Downloads](https://img.shields.io/packagist/dt/codegreencreative/laravel-aweber.svg?style=flat-square)](https://packagist.org/packages/codegreencreative/laravel-aweber)

# Laravel ^6.0|^7.0|^8.0|^9.0|^10.0 Aweber API Adapter

This package allows you to easily use the Aweber API using Laravel. You must first have created an Aweber App using their developer website.

Create your Aweber App: https://www.aweber.com/users/apps

# Latest Changes

Version 1.2.0

-   Added support for Laravel 7.0
-   Added support for Laravel 8.0
-   Added support for Laravel 9.0
-   Added support for Laravel 10.0
-   Fixed an issue where for every API call, an additional call to retrieve an account for a given token and secret is being made. If you pass an account ID when setting the consumer (`setConsumer`), it will no longer make that additional API call.
-   Updated the README file with more detailed instructions on how to use the library.

## Installation

Require this package with composer:

```shell
composer require codegreencreative/laravel-aweber
```

Publish config

```shell
php artisan vendor:publish --tag="aweber_config"
```

## Config

This Laravel + Aweber package will help you with OAuth authorization. By simply updating the `aweber.php` published config file, you can start making API calls to Aweber to manage your lists and subscribers.

By supplying your Aweber username (email address) and password along with your client id and client secret and redirect URL when you created your Aweber App, the OAuth flow is automated. The token to make API calls are stored in your default cache driver but can be changed in your ENV.

## Code Examples

Below you will find some of the functionality of this package. Not all are listed here.

#### Accounts

```php
// Paginate all accounts
// $start integer default 0
// $limit integer default 100 cannot be greater than 100
$accounts = Aweber::accounts()->paginate($start, $limit);

// Load a single account
// $account_id integer
$account = Aweber::accounts()->load($account_id);
// Will return the orignal account object returned by Aweber
$account->account;
```

#### Broadcasts

```php
// Paginate all broadcasts for a list based on status
// $start integer default 0
// $limit integer default 100 cannot be greater than 100
$broadcasts = Aweber::broadcasts()
    ->setList($list_id)
    ->status('draft')
    ->paginate($start, $limit);

// Load a single broadcast
// $account_id integer
$broadcast = Aweber::broadcasts()
    ->setList($list_id)
    ->load($broadcast_id);
// Will return the orignal broadcast object returned by Aweber
$broadcast->broadcast;
```

#### Campaigns

```php
// Paginate all campaigns for a list
// $start integer default 0
// $limit integer default 100 cannot be greater than 100
$campaigns = Aweber::campaigns()
    ->setList($list_id)
    ->paginate($start, $limit);

// Load a single campaign
// $account_id integer
$campaign = Aweber::campaigns()
    ->setList($list_id)
    ->load($campaign_id);
// Will return the orignal campaign object returned by Aweber
$campaign->campaign;
```

#### Custom Fields

```php
// Paginate all custom fields for a list
// $start integer default 0
// $limit integer default 100 cannot be greater than 100
$custom_fields = Aweber::customFields()
    ->setList($list_id)
    ->paginate($start, $limit);

// Load a single custom field
// $account_id integer
$custom_field = Aweber::customFields()
    ->setList($list_id)
    ->load($custom_field_id);
// Will return the orignal custom field object returned by Aweber
$custom_field->custom_field;
```

#### Lists

```php
// Paginate all lists
// $start integer default 0
// $limit integer default 100 cannot be greater than 100
$lists = Aweber::lists()->paginate($start, $limit);

// Load a single list
// $list_id integer
$list = Aweber::lists()->load($list_id);
// Will return the orignal list object returned by Aweber
$list->setList;

// This will return an array containing up to 500 tags
// sorted by descending popularity.
$tags = $list->tags;
// Get total subscribed subscribers for a list
$subscribers = $list->total_subscribed_subscribers;
// Get total subscribers for a list
$subscribers = $list->total_subscribers;
// Get total subscribers subscribed today for a list
$subscribers = $list->total_subscribers_subscribed_today;
// Get total subscribers subscribed yesterday for a list
$subscribers = $list->total_subscribers_subscribed_yesterday;
// Get total unconfirmed subscribers for a list
$subscribers = $list->total_unconfirmed_subscribers;
// Get total unsubscribed subscribers for a list
$subscribers = $list->total_unsubscribed_subscribers;
```

#### Subscribers

```php
// Paginate all subscribers on a list
// $start integer default 0
// $limit integer default 100 cannot be greater than 100
$lists = Aweber::subscribers()
    ->setList($list_id)
    ->paginate($start, $limit);

// Load a single subscriber
$subscriber = Aweber::subscribers()
    ->setList($list_id)
    ->find($subscriber_id);
// Will return the orignal subscriber object returned by Aweber
$list->setList;

// Add a subscriber to a list
$subscriber = Aweber::subscribers()
    ->setList($list_id)
    ->add([
        'custom_fields' => [
            'field' => 'value',
        ],
        'email' => 'test@test.com',
        'name' => 'Test Test',
        'strict_custom_fields' => true,
        'tags' => [],
    ]);

// Move a subscriber from one list to another
$subscriber = Aweber::subscribers()
    ->setList($list_id)
    ->load($subscriber_id);
$subscriber->move($destination_list_id);
```

## Connect to consumer accounts

If you wish to connect to your client's Aweber accounts, you can do so by updating the consumer token and secret via the `setConsumer` method per the following example:

```php
$subscriber = AweberFacade::subscribers()
    ->setConsumer($consumer_client_id, $consumer_client_secret, $account_id = null)
    ->setList($lead_bucket->list_id)
    ->find('test@example.com');
```

In version 1.2.0, you can pass an optional $account_id to the `setConsumer` method to connect to a specific account. This is useful if you have multiple accounts under your developer account or OAuth to connect to customer accounts.

```php

## Disclaimer

This package does not implement all functions of the Aweber API. Use at your own discretion.

## Contribution Guide

Should you add functionality to this package, please create a pull request. Code additions will only be considered through pull requests.

Code written must be compatible with Laravel 4.1+ and PHP 5.3+.
```
