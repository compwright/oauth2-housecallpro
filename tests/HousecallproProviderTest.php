<?php

declare(strict_types=1);

namespace CompWright\OAuth2\HousecallPro;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HousecallproProviderTest extends TestCase
{
    use QueryBuilderTrait;

    protected HousecallproProvider $provider;

    protected function setUp(): void
    {
        $factory = new HousecallproProviderFactory();
        $this->provider = $factory->new(
            'mock_client_id',
            'mock_secret'
        );
    }

    public function testGetAuthorizationUrl(): void
    {
        $actualUrl = $this->provider->getAuthorizationUrl([
            'redirect_uri' => 'https://foo.bar/redir',
            'state' => 'foo',
        ]);

        $expectedUrl = 'https://pro.housecallpro.com/oauth/authorize?redirect_uri=https%3A%2F%2Ffoo.bar%2Fredir&state=foo&response_type=code&approval_prompt=auto&client_id=mock_client_id';

        $this->assertEquals(
            $expectedUrl,
            $actualUrl
        );
    }

    public function testGetAccessToken(): void
    {
        $response = new Response(
            200,
            ['content-type' => 'application/json'],
            '{"access_token":"mock_access_token","token_type":"Bearer","expires_in":2592000,"refresh_token":"mock_refresh_token","scope":"public","created_at":"1750104252"}',
        );

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (Request $request) {
                    $this->assertSame('https://api.housecallpro.com/oauth/token', (string) $request->getUri());
                    $this->assertSame('POST', $request->getMethod());
                    $this->assertSame('application/json', $request->getHeaderLine('content-type'));
                    $this->assertSame(
                        '{"client_id":"mock_client_id","client_secret":"mock_secret","redirect_uri":"https:\/\/foo.bar","grant_type":"authorization_code","code":"foo"}',
                        (string) $request->getBody()
                    );
                    return true;
                })
            )
            ->willReturn($response);

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => 'foo',
            'redirect_uri' => 'https://foo.bar',
        ]);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData(): void
    {
        $id = (string) rand(1000, 9999);
        $name = uniqid();
        $support_email = uniqid();
        $logo_url = 'test_logo_url';

        $expectedRequestResponse = [
            // Get access token API call
            [
                $this->callback(function (Request $request) {
                    $this->assertSame('https://api.housecallpro.com/oauth/token', (string) $request->getUri());
                    $this->assertSame('POST', $request->getMethod());
                    $this->assertSame('application/json', $request->getHeaderLine('content-type'));
                    $this->assertSame(
                        '{"client_id":"mock_client_id","client_secret":"mock_secret","redirect_uri":"https:\/\/foo.bar","grant_type":"authorization_code","code":"foo"}',
                        (string) $request->getBody()
                    );
                    return true;
                }),
                new Response(
                    200,
                    ['content-type' => 'application/json'],
                    '{"access_token":"mock_access_token","token_type":"Bearer","expires_in":2592000,"refresh_token":"mock_refresh_token","scope":"public","created_at":"1750104252"}',
                )
            ],

            // Get resource owner API call
            [
                $this->callback(function (Request $request) {
                    $this->assertSame('https://api.housecallpro.com/company', (string) $request->getUri());
                    $this->assertSame('GET', $request->getMethod());
                    $this->assertSame('Bearer mock_access_token', $request->getHeaderLine('authorization'));
                    return true;
                }),
                new Response(
                    200,
                    ['content-type' => 'application/json'],
                    json_encode(compact('id', 'name', 'support_email', 'logo_url'), JSON_THROW_ON_ERROR)
                )
            ]
        ];

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function (Request $request) use (&$expectedRequestResponse): Response {
                /** @var array{\PHPUnit\Framework\Constraint\Callback<Request>, Response} $r */
                $r = array_shift($expectedRequestResponse);
                $requestAssertion = $r[0];
                $response = $r[1];
                if (true === $requestAssertion->evaluate($request, '', true)) {
                    return $response;
                }
                throw new RuntimeException('Unexpected request');
            });

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => 'foo',
            'redirect_uri' => 'https://foo.bar',
        ]);

        $this->assertInstanceOf(AccessToken::class, $token);

        $user = $this->provider->getResourceOwner($token);

        $this->assertInstanceOf(HousecallproResourceOwner::class, $user);

        $this->assertEquals($id, $user->getId());
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($support_email, $user->getEmail());
        $this->assertEquals($logo_url, $user->getLogoUrl());

        $this->assertEquals(
            json_encode(compact('id', 'name', 'support_email', 'logo_url')),
            json_encode($user)
        );
    }

    public function testExceptionThrownWhenErrorObjectReceived(): void
    {
        $status = rand(400, 599);
        $postResponse = new Response(
            $status,
            ['content-type' => 'application/json'],
            '{"error":"invalid_grant","error_description":"The provided authorization grant is invalid, expired, revoked, does not match the redirection URI used in the authorization request, or was issued to another client."}'
        );

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('send')
            ->willReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionCode($status);
        $this->expectExceptionMessage('The provided authorization grant is invalid, expired, revoked, does not match the redirection URI used in the authorization request, or was issued to another client.');

        $this->provider->getAccessToken('client_credentials');
    }

    public function testExceptionThrownWhenOAuthErrorReceived(): void
    {
        $postResponse = new Response(
            200,
            ['content-type' => 'application/json'],
            '{"error":"invalid_grant","error_description":"The provided authorization grant is invalid, expired, revoked, does not match the redirection URI used in the authorization request, or was issued to another client."}'
        );

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('send')
            ->willReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionCode(200);
        $this->expectExceptionMessage('The provided authorization grant is invalid, expired, revoked, does not match the redirection URI used in the authorization request, or was issued to another client.');

        $this->provider->getAccessToken('client_credentials');
    }
}
