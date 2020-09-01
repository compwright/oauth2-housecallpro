<?php

namespace Compwright\OAuth2_Housecallpro;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Provider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return 'https://api.housecallpro.com/oauth/authorize';
    }

    /**
     * Get access token url to retrieve token
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://api.housecallpro.com/oauth/token';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param  AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://api.housecallpro.com/alpha/organization/profile';
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  array $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['type']) && $data['type'] === 'error') {
            throw new IdentityProviderException(
                $data['message'],
                $data['status'],
                $response
            );
        }

        // Handle Oauth2 Error
        if (isset($data['error'], $data['error_description'])) {
            throw new IdentityProviderException(
                $data['error_description'],
                $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param object $response
     * @param AccessToken $token
     * @return ResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ResourceOwner($response);
    }
}
