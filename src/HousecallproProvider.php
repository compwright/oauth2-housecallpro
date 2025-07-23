<?php

declare(strict_types=1);

namespace CompWright\OAuth2\HousecallPro;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

/**
 * @method HousecallproResourceOwner getResourceOwner(AccessToken $token)
 */
class HousecallproProvider extends GenericProvider
{
    /**
     * @inheritdoc
     * 
     * @param array<string, mixed> $response
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new HousecallproResourceOwner($response, 'id');
    }

    /**
     * @inheritdoc
     * 
     * @param array<string, string|int|bool|null> $data
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        $status = $response->getStatusCode();
        $error = $data['error_description'] ?? $data['error'] ?? null;
        if ($error || $status >= 400) {
            throw new IdentityProviderException(strval($error), $status, $data);
        }
    }
}
