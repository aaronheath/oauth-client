# OAuth Client

[![Build Status](https://travis-ci.org/aaronheath/oauth-client.svg?branch=master)](https://travis-ci.org/aaronheath/oauth-client)

## Introduction

This is a personal package which provides a simple OAuth2 client. The client is issued with a static [personal access token](https://laravel.com/docs/7.x/passport#personal-access-tokens).

## Installation

This package is installed via [Composer](https://getcomposer.org/). 

Before installing, the repository must be added to the repositories section of the host projects composer.json.

```text
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/aaronheath/oauth-client"
    }
],
```

To install, run the following command.

```bash
composer require aaronheath/oauth-client
```

Then, publish the configuration file. A new file will be created at config/oauth-client.php

```bash
php artisan vendor:publish
```

Finally, at minimum you'll want to configure a default OAuth server by updating the projects .env file.

```text
OAUTH_CLIENT_URL=https://example.com/oauth/token
OAUTH_CLIENT_ID=client_id
OAUTH_CLIENT_SECRET=client_secret
```
Additional OAuth hosts can be configured in the config file.
