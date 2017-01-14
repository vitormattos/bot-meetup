<?php
use Base\DB;
use Base\Meetup;
use Aura\SqlQuery\QueryFactory;
use Aura\Sql\Exception;
use Base\Api;

require_once 'vendor/autoload.php';

if(file_exists('.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
}

// If we don't have an authorization code then get one
if (!isset($_GET['code']) || !preg_match('/^[a-f0-9]{32}$/', $_GET['code'])) {
    exit('Invalid access');
} else {
    try {
        $db = DB::getInstance();
        $query_factory = new QueryFactory($db->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $select = $query_factory->newSelect();
        $select
            ->cols([
                'telegram_id'
            ])
            ->from('userdata')
            ->where('oauth2state = :oauth2state')
            ->bindValue('oauth2state', $_GET['state']);
        $sth = $db->prepare($select->getStatement());
        $sth->execute($select->getBindValues());
        $user = $sth->fetch(\PDO::FETCH_ASSOC);
        if(!$user) {
            throw new Exception('Usuário inválido');
        }

        $provider = Meetup::getProvider();
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        $update = $query_factory->newUpdate();
        $update
            ->table('userdata')
            ->set('token', ':token')
            ->set('updated_at', 'now()')
            ->bindValue('token', $accessToken->getToken())
            ->where('oauth2state = :state')
            ->bindValue('state', $_GET['state']);
        $sth = $db->prepare($update->getStatement());
        $sth->execute($update->getBindValues());

        $telegram = new Api();
        $telegram->sendMessage([
            'chat_id' => $user['telegram_id'],
            'text' => 'Bem vindo '. $ownerDetails['name'] . '!'
        ]);
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        // Failed to get the access token or user details.
        if(isset($user) && $user) {
            $telegram = new Api();
            $telegram->sendMessage([
                'chat_id' => $user['telegram_id'],
                'text' => $e->getMessage()
            ]);
        } else {
            echo $e->getMessage();
        }
    }
}
header(
    'Location: https://telegram.me/'.getenv('TELEGRAM_USERNAME')
);