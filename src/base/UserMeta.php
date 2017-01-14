<?php
namespace Base;
use Aura\SqlQuery\QueryFactory;
use League\OAuth2\Client\Token\AccessToken;

class UserMeta
{
    /**
     *
     * @param int $telegram_id
     * @param int $oauth2state
     * @throws \Exception
     */
    public function getUser($telegram_id = null)
    {
        $accessToken = $this->getAccessToken($telegram_id);
        $provider = Meetup::getProvider();
        $db = DB::getInstance();
        $ownerDetails = $provider->getResourceOwner($accessToken)->toArray();
        if(array_key_exists('problem', $ownerDetails)) {
            $db->perform('DELETE FROM userdata WHERE telegram_id = :telegram_id', [
                'telegram_id' => $telegram_id
            ]);
            throw new \Exception($ownerDetails['problem'], 1);
        }
        return $ownerDetails;
    }
    
    /**
     * Get the access token from current user
     *
     * @param int $telegram_id
     * @param int $oauth2state
     * @throws \Exception
     * @return \League\OAuth2\Client\Token\AccessToken Access Token
     */
    public static function getAccessToken($telegram_id = null)
    {
        $db = DB::getInstance();
        $query_factory = new QueryFactory($db->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $select = $query_factory->newSelect();
        $select
            ->cols([
                'token AS access_token'
            ])
            ->from('userdata')
            ->where('telegram_id = :telegram_id')
            ->bindValue('telegram_id', $telegram_id);
        $sth = $db->prepare($select->getStatement());
        $sth->execute($select->getBindValues());
        $token = $sth->fetch(\PDO::FETCH_ASSOC);
        if(!$token) {
            throw new \Exception('É preciso autenticar-se.', 1);
        }
        if(!$token['access_token']) {
            $db->perform('DELETE FROM userdata WHERE telegram_id = :telegram_id', [
                'telegram_id' => $telegram_id
            ]);
            throw new \Exception('É preciso autenticar-se.', 1);
        }
        return new AccessToken($token);
    }
}