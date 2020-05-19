<?php

namespace Heath\OauthClient;

use GuzzleHttp\Client;

class OauthClient
{
    protected $currentProfile = 'default';
    protected $cacheDurationSubtraction = 10; // seconds
//    protected $profiles;

    public function __construct()
    {
//        $this->profiles = [
//            [
//                'name' => 'changi',
//                'url' => 'https://api.aaronheath.com/oauth/token',
//                'client_id' => config('site.changi.client_id'),
//                'client_secret' => config('site.changi.client_secret'),
//                'verify_https' => config('site.changi.verify_https'),
//                'scope' => config('site.changi.verify_https'),
//            ],
//        ];
//
//        if(config('app.env') === 'testing') {
//            $this->profiles[] = [
//                'name' => 'testing',
//                'url' => 'https://api.test/oauth/token',
//                'client_id' => 'abc',
//                'client_secret' => '123',
//                'verify_https' => false,
//            ];
//        }
    }

    public function profile()
    {
        return $this->currentProfile;
    }

//    public function loadProfile($config)
//    {
//        $this->profiles[] = $config;
//
//        return $this;
//    }

    public function useProfile($name)
    {
//        $this->getProfile($name);

        if(! config()->has('oauth-client.profiles.' . $name)) {
            throw new OauthClientException('Profile not found.');
        }

        $this->currentProfile = $name;

        return $this;
    }

    public function withToken($guzzleOptions = [])
    {
        return array_replace_recursive(
            $guzzleOptions,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token(),
                ],
                'verify' => $this->fromProfile('verify_https'),
            ]
        );
    }

    public function token()
    {
        if(cache()->has($this->cacheKey())) {
            return cache()->get($this->cacheKey());
        }

        $response = app(Client::class)->post($this->fromProfile('url'), [
            'verify' => $this->fromProfile('verify_https'),
            'json' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->fromProfile('client_id'),
                'client_secret' => $this->fromProfile('client_secret'),
                'scope' => $this->fromProfile('scope'),
            ],
        ]);

        $body = json_decode((string) $response->getBody());

        cache()->put(
            $this->cacheKey(),
            $body->access_token,
            now()->addSeconds($body->expires_in - $this->cacheDurationSubtraction)
        );

        return cache()->get($this->cacheKey());
    }

    protected function fromProfile($attribute)
    {
        $profile = $this->getProfile($this->currentProfile);

        if(! isset($profile[$attribute])) {
            throw new OauthClientException(sprintf('Profile does not have %s attribute.', $attribute));
        }

        return $profile[$attribute];
    }

    protected function cacheKey()
    {
        return sprintf('oauth.%s.access_token', $this->currentProfile);
    }

    protected function getProfile($name)
    {
        return config('oauth-client.profiles.' . $this->profile());

//        $profile = collect($this->profiles)->where('name', $name);
//
//        if(! $profile->count()) {
//            throw new OauthClientException('Profile not found.');
//        }
//
//        return $profile->first();
    }
}