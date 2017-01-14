<?php
namespace Base;

class Meetup extends \League\OAuth2\Client\Provider\GenericProvider
{
    private $token;
    public static function getProvider()
    {
        return new self([
            'clientId'                => getenv('CLIENT_ID'),
            'clientSecret'            => getenv('CLIENT_SECRET'),
            'redirectUri'             => getenv('REDIRECT_URI'),
            'urlAuthorize'            => 'https://secure.meetup.com/oauth2/authorize',
            'urlAccessToken'          => 'https://secure.meetup.com/oauth2/access',
            'urlResourceOwnerDetails' => 'https://api.meetup.com/2/member/self'
        ]);
    }
    
    public function setToken($token)
    {
        $this->token = $token;
    }
    
    public function getCommand($method, $path, $args = null)
    {
        $factory = $this->getRequestFactory();
        $request = $factory->getRequest($method, 'https://api.meetup.com'.$path,
            [
                'Authorization' => 'Bearer '.$this->token,
                'Content-Type'=>'application/x-www-form-urlencoded'
            ],
            $args
        );
        $response = $this->sendRequest($request);
        $parsed = $this->parseResponse($response);

        $this->checkResponse($response, $parsed);

        return $parsed;
    }
}