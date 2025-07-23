<?php

declare(strict_types=1);

namespace CompWright\OAuth2\HousecallPro;

use JsonSerializable;
use League\OAuth2\Client\Provider\GenericResourceOwner;

class HousecallproResourceOwner extends GenericResourceOwner implements JsonSerializable
{
    public function getEmail(): ?string
    {
        return $this->response['support_email'] ?: null;
    }

    public function getName(): ?string
    {
        return $this->response['name'] ?: null;
    }

    public function getLogoUrl(): ?string
    {
        return $this->response['logo_url'] ?: null;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
