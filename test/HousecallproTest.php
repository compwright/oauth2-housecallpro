<?php

namespace Compwright\OAuth2_Housecallpro\Test;

use Compwright\OAuth2_Housecallpro\Provider;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class HousecallproTest extends TestCase
{
    use QueryBuilderTrait;

    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new Provider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes()
    {
        $scopeSeparator = ',';
        $options = ['scope' => [uniqid(), uniqid()]];
        $query = ['scope' => implode($scopeSeparator, $options['scope'])];
        $url = $this->provider->getAuthorizationUrl($options);
        $encodedScope = $this->buildQueryString($query);
        $this->assertStringContainsString($encodedScope, $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"access_token": "mock_access_token","expires_in": 3600,"restricted_to": [],"token_type": "bearer","refresh_token": "mock_refresh_token"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertLessThanOrEqual(time() + 3600, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $email = uniqid();
        $name = uniqid();
        $logoUrl = uniqid();
        $userId = rand(1000,9999);

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"access_token": "mock_access_token","expires_in": 3600,"restricted_to": [],"token_type": "bearer","refresh_token": "mock_refresh_token"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{"object":"organization","id":"'.$userId.'","phone_number":"8585551234","support_email":"'.$email.'","name":"'.$name.'","logo_url":"'.$logoUrl.'","description":"My customer facing profile","printable_address":"123 Jump St. #44, San Diego, CA 92122","url":"/organizations/26127cfb-d36d-4fb1-a930-6a0f07fce65e","address":{"object":"organization_address","id":"26127cfb-d36d-4fb1-a930-6a0f07fce65e","street":"123 Jump St.","street_line_2":"#44","city":"San Diego","state":"CA","zip":"92122","latitude":"33.062759135379856","longitude":"-116.94116732622786"},"website":"company.com","default_arrival_window":0,"default_industry":{"id":"ind_ac53d778d6a740e7a162fd4ce9b68a1a","name":"Carpet Cleaning","asset_id":"CARPET"},"declared_industry":{"id":"ind_ac53d778d6a740e7a162fd4ce9b68a1a","name":"Plumbing","asset_id":"PLUMBING"},"declared_employee_count":1,"terms_url":"https://pro.housecall.io/MyCompanyInc/1/terms","time_zone":"America/Los_Angeles"}');
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->toArray()['support_email']);
        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($userId, $user->toArray()['id']);
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($name, $user->toArray()['name']);
        $this->assertEquals($logoUrl, $user->getLogoUrl());
        $this->assertEquals($logoUrl, $user->toArray()['logo_url']);
    }

    public function testOauth2Error()
    {
        $this->expectException(\League\OAuth2\Client\Provider\Exception\IdentityProviderException::class);

        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"error": "invalid_grant", "error_description": "Invalid refresh token"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(400);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('refresh_token', ['refresh_token' => 'mock_refresh_token']);
    }
}
