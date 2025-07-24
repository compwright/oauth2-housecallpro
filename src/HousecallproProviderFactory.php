<?php

declare(strict_types=1);

namespace CompWright\OAuth2\HousecallPro;

use GuzzleHttp\ClientInterface;

class HousecallproProviderFactory
{
    /** @see https://docs.housecallpro.com/docs/housecall-public-api/b87d37ae48a0d-authentication#-2-initiate-the-oauth-flow */
    public const API_AUTHORIZATION = 'https://pro.housecallpro.com/oauth/authorize';

    /** @see https://docs.housecallpro.com/docs/housecall-public-api/b87d37ae48a0d-authentication#-4-exchange-authorization-code-for-access-token */
    public const API_TOKEN = 'https://api.housecallpro.com/oauth/token';

    /** @see https://docs.housecallpro.com/docs/housecall-public-api/24a5d891a80a8-get-company */
    public const API_RESOURCE_OWNER = 'https://api.housecallpro.com/company';

    public function __construct(private ?ClientInterface $httpClient = null)
    {
    }

    public function new(
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $redirectUri = null
    ): HousecallproProvider {
        $provider = new HousecallproProvider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
            'urlAccessToken' => self::API_TOKEN,
            'urlAuthorize' => self::API_AUTHORIZATION,
            'urlResourceOwnerDetails' => self::API_RESOURCE_OWNER,
        ]);

        $provider->setOptionProvider(new JsonPostAuthOptionProvider());

        if ($this->httpClient) {
            $provider->setHttpClient($this->httpClient);
        }

        return $provider;
    }
}
