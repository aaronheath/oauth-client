<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Heath\OauthClient\OauthClient;
use Heath\OauthClient\OauthClientException;
use Heath\OauthClient\OauthClientServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class OauthClientTest extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            OauthClientServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('oauth-client', require __DIR__.'/../src/config.php');
    }

    /**
     * @test
     */
    public function can_request_new_token()
    {
        config([
            'oauth-client.profiles.default.client_id' => 'abc123',
            'oauth-client.profiles.default.client_secret' => 'xyz987',
            'oauth-client.profiles.default.verify_https' => true,
        ]);

        $requests = [];

        $history = Middleware::history($requests);

        $responses = new MockHandler([
            new Response(200, ['content-type' => 'application/json'], json_encode([
                'token_type' => 'Bearer',
                'expires_in' => 3000,
                'access_token' => 'yyy',
                'expires_at' => '2019-04-01T00:01:02.001Z',
            ])),
        ]);

        $handler = HandlerStack::create($responses);
        $handler->push($history);

        $client = new Client(['handler' => $handler]);

        $this->app->instance(Client::class, $client);

        $this->assertEquals('yyy' , (new OauthClient)->token());
        $this->assertEquals('yyy', cache()->get('oauth.default.access_token'));

        $this->assertEquals('POST', $requests[0]['request']->getMethod());
        $this->assertEquals('https://example.com/oauth/token', $requests[0]['request']->getUri());
        $this->assertTrue($requests[0]['options']['verify']);
        $this->assertEquals(
            json_encode([
                'grant_type' => 'client_credentials',
                'client_id' => 'abc123',
                'client_secret' => 'xyz987',
                'scope' => '',
            ]),
            (string) $requests[0]['request']->getBody()
        );
    }

    /**
     * @test
     */
    public function can_use_existing_token()
    {
        cache()->put('oauth.default.access_token', 'xxx', now()->addHour());

        $this->assertEquals('xxx' , (new OauthClient)->token());
    }

    /**
     * @test
     */
    public function uses_default_profile_as_default()
    {
        $this->assertEquals('default', (new OauthClient)->profile());
    }

    /**
     * @test
     */
    public function fails_if_profile_not_found()
    {
        $this->expectException(OauthClientException::class);
        $this->expectExceptionMessage('Profile not found.');

        (new OauthClient)->useProfile('xxx');
    }

    /**
     * @test
     */
    public function can_use_non_default_profile()
    {
        $url = 'https://api.test/oauth/token';
        $clientId = 'abc';
        $clientSecret = '123';

        config([
            'oauth-client.profiles.testing.url' => $url,
            'oauth-client.profiles.testing.client_id' => $clientId,
            'oauth-client.profiles.testing.client_secret' => $clientSecret,
            'oauth-client.profiles.testing.verify_https' => false,
            'oauth-client.profiles.testing.scope' => '',
        ]);

        $requests = [];

        $history = Middleware::history($requests);

        $responses = new MockHandler([
            new Response(200, ['content-type' => 'application/json'], json_encode([
                'token_type' => 'Bearer',
                'expires_in' => 3000,
                'access_token' => 'testing',
                'expires_at' => '2019-04-01T00:01:02.001Z',
            ])),
        ]);

        $handler = HandlerStack::create($responses);
        $handler->push($history);

        $client = new Client(['handler' => $handler]);

        $this->app->instance(Client::class, $client);

        $this->assertEquals('testing' , (new OauthClient)->useProfile('testing')->token());
        $this->assertEquals('testing', cache()->get('oauth.testing.access_token'));

        $this->assertEquals('POST', $requests[0]['request']->getMethod());
        $this->assertEquals($url, $requests[0]['request']->getUri());
        $this->assertFalse($requests[0]['options']['verify']);
        $this->assertEquals(
            json_encode([
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => '',
            ]),
            (string) $requests[0]['request']->getBody()
        );
    }

//    /**
//     * @test
//     */
//    public function can_load_own_run_time_profile()
//    {
//        $url = 'https://api.example.com/oauth/token';
//        $clientId = 'abc';
//        $clientSecret = '123';
//
//        $requests = [];
//
//        $history = Middleware::history($requests);
//
//        $responses = new MockHandler([
//            new Response(200, ['content-type' => 'application/json'], json_encode([
//                'token_type' => 'Bearer',
//                'expires_in' => 3000,
//                'access_token' => 'run-time-token',
//                'expires_at' => '2019-04-01T00:01:02.001Z',
//            ])),
//        ]);
//
//        $handler = HandlerStack::create($responses);
//        $handler->push($history);
//
//        $client = new Client(['handler' => $handler]);
//
//        $this->app->instance(Client::class, $client);
//
//        $token = (new OauthClient)
//            ->loadProfile([
//                'name' => 'run-time-loaded',
//                'url' => $url,
//                'client_id' => $clientId,
//                'client_secret' => $clientSecret,
//                'verify_https' => false,
//            ])
//            ->useProfile('run-time-loaded')
//            ->token();
//
//        $this->assertEquals('run-time-token' , $token);
//        $this->assertEquals('run-time-token', cache()->get('oauth.run-time-loaded.access_token'));
//
//        $this->assertEquals('POST', $requests[0]['request']->getMethod());
//        $this->assertEquals($url, $requests[0]['request']->getUri());
//
//        $this->assertEquals(
//            json_encode([
//                'grant_type' => 'client_credentials',
//                'client_id' => $clientId,
//                'client_secret' => $clientSecret,
//                'scope' => '',
//            ]),
//            (string) $requests[0]['request']->getBody()
//        );
//    }

    /**
     * @test
     */
    public function with_token()
    {
        $token = 'abc';

        $withoutToken = [
            'json' => [
                'a' => 1,
                'b' => 2,
                'c' => 3
            ],
        ];
        $withToken = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'verify'  => true,
            'json' => [
                'a' => 1,
                'b' => 2,
                'c' => 3
            ],
        ];

//        config([
//            'site.changi.client_id' => 'abc123',
//            'site.changi.client_secret' => 'xyz987',
//        ]);

        config([
            'oauth-client.profiles.default.client_id' => 'abc123',
            'oauth-client.profiles.default.client_secret' => 'xyz987',
        ]);

        $requests = [];

        $history = Middleware::history($requests);

        $responses = new MockHandler([
            new Response(200, ['content-type' => 'application/json'], json_encode([
                'token_type' => 'Bearer',
                'expires_in' => 3000,
                'access_token' => $token,
                'expires_at' => '2019-04-01T00:01:02.001Z',
            ])),
        ]);

        $handler = HandlerStack::create($responses);
        $handler->push($history);

        $client = new Client(['handler' => $handler]);

        $this->app->instance(Client::class, $client);

        $this->assertEquals($withToken , (new OauthClient)->withToken($withoutToken));
        $this->assertEquals($token, cache()->get('oauth.default.access_token'));

        $this->assertEquals('POST', $requests[0]['request']->getMethod());
        $this->assertEquals('https://example.com/oauth/token', $requests[0]['request']->getUri());
        $this->assertEquals(
            json_encode([
                'grant_type' => 'client_credentials',
                'client_id' => 'abc123',
                'client_secret' => 'xyz987',
                'scope' => '',
            ]),
            (string) $requests[0]['request']->getBody()
        );
    }
}
