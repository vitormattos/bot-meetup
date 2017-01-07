<?php
use Base\DB;
use Base\Meetup;
use Aura\SqlQuery\QueryFactory;

require_once 'vendor/autoload.php';

if(file_exists('.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
}

$provider = Meetup::getProvider();

// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {
    exit('Invalid access');
} else {
    try {
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        $db = DB::getInstance();
        $query_factory = new QueryFactory($db->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $update = $query_factory->newUpdate();

        $update
            ->table('userdata')
            ->set('token', ':token')
            ->set('updated_at', 'now()')
            ->bindValue('token', $accessToken->getToken())
            ->where('oauth2state = :state')
            ->bindValue('state', $md5token = md5($_GET['state']));
        $sth = $db->prepare($update->getStatement());
        $sth->execute($update->getBindValues());
        header(
            'Location: https://telegram.me/'.getenv('TELEGRAM_USERNAME').
            '?start='.$md5token
        );
        exit;
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        // Failed to get the access token or user details.
        exit($e->getMessage());
    }
}