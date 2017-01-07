<?php
namespace Base;

class Meetup extends \League\OAuth2\Client\Provider\GenericProvider
{
    public static function getProvider()
    {
        return new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => getenv('CLIENT_ID'),
            'clientSecret'            => getenv('CLIENT_SECRET'),
            'redirectUri'             => getenv('REDIRECT_URI'),
            'urlAuthorize'            => 'https://secure.meetup.com/oauth2/authorize',
            'urlAccessToken'          => 'https://secure.meetup.com/oauth2/access',
            'urlResourceOwnerDetails' => 'https://api.meetup.com/2/member/self'
        ]);
    }
}