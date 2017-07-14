# Laravel Shopify App

[![Build Status](https://secure.travis-ci.org/ohmybrew/laravel-shopify.png?branch=master)](http://travis-ci.org/ohmybrew/laravel-shopify)
[![Coverage Status](https://coveralls.io/repos/github/ohmybrew/laravel-shopify/badge.svg?branch=master)](https://coveralls.io/github/ohmybrew/laravel-shopify?branch=master)

A Laravel package for aiding in Shopify App development, similar to `shopify_app` for Rails.

![Screenshot](https://github.com/ohmybrew/laravel-shopify/raw/master/docs/screenshot.png)

## Table of Contents

- [Goals](#goals)
- [Requirements](#requirements)
- [Installation](#installation)
  - [Providers](#providers)
  - [Facades](#facades)
  - [Middlewares](#middlewares)
  - [Jobs](#jobs)
  - [Migrations](#migrations)
  - [Configuration](#configuration)
    - [Package](#package)
    - [Shopify App](#shopify-app)
- [Documentation](#documentation)
- [Route List](#route-list)
- [Usage](#usage)
  - [Accessing the current shop](#accessing-the-current-shop)
  - [Accessing API for the current shop](#accessing-api-for-the-current-shop)
- [LICENSE](#license)

## Goals

- [x] Provide assistance in developing Shopify apps with Laravel
- [x] Integration with Shopify API
- [x] Authentication & installation for shops
- [x] Auto install app webhooks and scripttags thorugh background jobs
- [x] Provide basic ESDK views

## Requirements

Here are the requirements to run this Laravel package.

| Package                       | Version   | Notes                                    |
| ----------------------------- |:---------:|:---------------------------------------- |
| `php`                         | 7         | Due to `ohmybrew/basic-shopify-api`      |
| `laravel/framework`           | 5.4.*     | For the package to work ;)               |
| `ohmybrew/basic-shopify-api`  | 1.0.*     | For API calls to Shopify                 |

## Installation

### Providers

Open `config/app.php` find `providers` array. Find a line with:

```php
App\Providers\RouteServiceProvider::class,
```

Before it, add a new line with:

```php
\OhMyBrew\ShopifyApp\ShopifyAppProvider::class,
```

This ensures you can override the default routes.

### Facades

Open `config/app.php` find `aliases` array. Add a new line with:

```php
'ShopifyApp' => \OhMyBrew\ShopifyApp\Facades\ShopifyAppFacade::class,
```

### Middlewares

Open `app/Http/Kernel.php` find `routeMiddleware` array. Add a new line with:

```php
'auth.shop' => \OhMyBrew\ShopifyApp\Middleware\AuthShop::class,
```

### Jobs

*Recommendations*

By default Laravel uses the `sync` driver to process jobs. These jobs run immediately and synchronously (blocking).

This package uses jobs to install webhooks and scripttags if any are defined in the configuration. If you do not have any scripttags or webhooks to install on the shop, you may skip this section.

If you do however, you can leave the `sync` driver as default. But, it may impact load times for the customer accessing the app. Its recommended to setup Redis or database as your default driver in `config/queue.php`. See [Laravel's docs on setting up queue drivers](https://laravel.com/docs/5.4/queues).

### Migrations

```bash
php artisan migrate
```

### Configuration

#### Package

```bash
php artisan vendor:publish
```

You're now able to access config in `config/shopify-app.php`. Essentially you will need to fill in the `app_name`, `api_key`, `api_secret`, and `api_scopes` to generate a working app. Items like `webhooks` and `scripttags` are completely optional depending on your app requirements.

#### Shopify App

In your app's settings on your Shopify Partner dashboard, you need to set the callback URL to be:

```bash
https://(your-domain).com/
```

And the `redirect_uri` to be:

```bash
https://(your-domain).com/authenticate
```

The callback URL will point to the home route, while the `redirect_uri` will point to the authentication route.

## Documentation

Information on getting started, overriding routes, controllers, is located in the `docs` directory of this repo.

- [Becoming a Shopify Developer](docs/becoming-a-shopify-developer.md)
- [Process in Authentication](docs/process-in-authentication.md)
- [Developing Locally](docs/developing-locally.md)
- [Overriding / Extending Package](docs/overriding-and-extending.md)

## Route List

Here are the defined routes and what they do.

| Route                     | Notes                                        |
| ------------------------- |:-------------------------------------------- |
| GET /                     | Displays home of app for authenticated shops |
| GET /login                | Displays login/install page                  |
| POST/GET /authenticate    | Authenticates the shop/installs the shop     |

## Usage

### Accessing the current shop

Using the facade:

```php
// Returns instance of \OhMyBrew\ShopifyApp\Models\Shop
ShopifyApp::shop()
```

### Accessing API for the current shop

```php
// Returns instance of \OhMyBrew\BasicShopifyAPI (ohmybrew/basic-shopify-api)
$shop = ShopifyApp::shop();
$shop->api()->request(...);
```

## LICENSE

This project is released under the MIT license.