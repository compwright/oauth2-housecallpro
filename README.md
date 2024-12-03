# Housecall Pro Provider for OAuth 2.0 Client

[![Latest Version](https://img.shields.io/github/release/compwright/oauth2-housecallpro.svg?style=flat-square)](https://github.com/compwright/oauth2-housecallpro/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/compwright/oauth2-housecallpro.svg?style=flat-square)](https://packagist.org/packages/compwright/oauth2-housecallpro)

This package provides Housecall Pro OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require compwright/oauth2-housecallpro league/oauth2-client
```

## Usage

Usage is the same as The League's OAuth client, using `\Compwright\OAuth2_Housecallpro\Provider` as the provider.

### Example: Authorization Code Flow

```php
$provider = new Compwright\OAuth2_Housecallpro\Provider([
    'clientId'      => '{housecallpro-client-id}',
    'clientSecret'  => '{housecallpro-client-secret}',
    'redirectUri'   => 'https://example.com/callback-url'
]);

if (!isset($_GET['code'])) {
    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;
}

// Check given state against previously stored one to mitigate CSRF attack
if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
}

// Get an access token using the authorization code grant
$token = $provider->getAccessToken('authorization_code', [
    'code' => $_GET['code']
]);

// You can look up a users profile data
$user = $provider->getResourceOwner($token);
printf('Hello %s!', $user->getId());

// Use the token to interact with an API on the users behalf
echo $token->getToken();
```

## Testing

``` bash
$ composer run-script test
```

## Contributing

Please see [CONTRIBUTING](https://github.com/compwright/oauth2-housecallpro/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Jonathon Hill](https://github.com/compwright)
- [Steven Maguire](https://github.com/stevenmaguire) for the Box provider package, which this package is forked form
- [All Contributors](https://github.com/compwright/oauth2-housecallpro/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/compwright/oauth2-housecallpro/blob/master/LICENSE) for more information.
