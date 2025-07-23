<?php

declare(strict_types=1);

namespace CompWright\OAuth2\HousecallPro;

use League\OAuth2\Client\OptionProvider\OptionProviderInterface;
use League\OAuth2\Client\Provider\AbstractProvider;

class JsonPostAuthOptionProvider implements OptionProviderInterface
{
    /**
     * @inheritdoc
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function getAccessTokenOptions($method, array $params)
    {
        $options = [
            'headers' => ['content-type' => 'application/json'],
        ];

        if ($method === AbstractProvider::METHOD_POST) {
            $options['body'] = json_encode($params, JSON_THROW_ON_ERROR);
        }

        return $options;
    }
}
