# Laravel + Aweber

This package allows you to easily use the Aweber API using Laravel. You must first have created an Aweber App using their developer website.

Create your Aweber App: https://www.aweber.com/users/apps

This package does not assume you will be connecting to your customers Aweber accounts. It is meant to connect to your own Aweber account and manage your own lists and subscribers.

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

This Laravel + Aweber package will help you with OAuth2 authorization. By simply updating the `aweber.php` published config file, you can start making API calls to Aweber to manage your lists and subscribers.

By supplying your Aweber username (email address) and password along with your client id and client secret and redirect URL when you created your Aweber App, the OAuth flow is automated. The token to make API calls are stored in your default cache driver, but can be changed in your ENV.

## Code Examples

Below you will find some of the functionality of this package. Not all are listed here.

```php
// Returns all lists available in your account
$lists = Aweber::lists()->all();

// Find a specific list
$list = Aweber::lists()->find($list_id);

// Get subscribers on a list
$subscribers = Aweber::lists($list_id)->subscribers;
$subscribers = Aweber::subscribers($list_id)->all();

// Get all campaigns on a list
$campaigns = Aweber::lists($list_id)->campaigns;

// Get all custom fields on a list
$custom_fields = Aweber::lists($list_id)->custom_fields;

// Get tags on a list
$tags = Aweber::lists()->tags($list_id);

// Get a subscriber
$subscriber = Aweber::subscribers($list_id)->find($subscriber_id);
$subscriber = Aweber::subscribers($list_id, $subscriber_id);

// Move a subscriber from one list to another
$subscriber = Aweber::subscribers($list_id, $subscriber_id);
$subscriber->move($destination_list_id);
```

## Disclaimer

This package does not implement all functions of the Aweber API. Use at your own discretion.

## Contribution Guide
Should you add functionality to this package, please create a pull request. Code additions will only be considered through pull requests.