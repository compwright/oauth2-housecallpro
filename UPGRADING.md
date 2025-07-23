# Upgrading

## 2.x to 3.x

Version 3.x represents a complete re-write which breaks from previous versions. The new implementation is simpler, relies on fewer dependencies, and leverages language features introduced in PHP 8.x.

### Updated PHP requirement

The minimum PHP version required is 8.x.

### Namespace and class name changes

* Package namespace has changed from `CompWright\OAuth2_Housecallpro` to `CompWright\OAuth2\HousecallPro`
* `Provider` class has been renamed to `HousecallproProvider`
* `ResourceOwner` class has been renamed to `HousecallproResourceOwner`

### New factory class

A factory class has been introduced, use `HousecallproProviderFactory::new()` to set up a new provider class instance.

### Internal changes

Internally, more reliance is made on the built in `league/oauth2-client` components.

* `HousecallproProvider` now extends `GenericProvider` rather than `AbstractProvider`
* `HousecallproResourceOwner` now extends `GenericResourceOwner`

### Bug fixes

* Corrects the authorization code URL domain to pro.housecallpro.com
* Corrects the access token request body content type from `application/x-www-form-urlencoded` to `application/json`
* Fixes a Google SSO error during the authentication flow
