<?php
require_once 'vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

session_start();

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => getenv('CLIENT_ID'),
    'clientSecret'            => getenv('CLIENT_SECRET'),
    'redirectUri'             => getenv('REDIRECT_URI'),
    'urlAuthorize'            => 'https://secure.meetup.com/oauth2/authorize',
    'urlAccessToken'          => 'https://secure.meetup.com/oauth2/access',
    'urlResourceOwnerDetails' => 'https://api.meetup.com/2/member/self'
]);

// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {

    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;
    // Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
} else {
    try {
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        $instance = DB::getInstance();
        try {
            $sth = $instance->perform('INSERT INTO user (token, md5token) VALUES(:token, :md5token) RETURNING md5token;', [
                'token' => $accessToken->getToken(),
                'md5token' => md5($accessToken->getToken())
            ]);
            $md5token = $sth->fetchColumn();
            header(
                'Location: https://telegram.me/'.getenv('TELEGRAM_USERNAME').
                '?start='.$md5token
            );
        } catch(Exception $e) {
            echo $e->getMessage();
        }
        exit;
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        // Failed to get the access token or user details.
        exit($e->getMessage());
    }

}