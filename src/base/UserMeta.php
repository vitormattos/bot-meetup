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
    public function getUser($telegram_id = null, $oauth2state = null)
    {
        $accessToken = $this->getAccessToken($telegram_id, $oauth2state); $provider = Meetup::getProvider();
        $db = DB::getInstance();
        $ownerDetails = $provider->getResourceOwner($accessToken)->toArray();
        if(array_key_exists('problem', $ownerDetails)) {
            $db->perform('DELETE FROM userdata WHERE telegram_id = :telegram_id', [
                'telegram_id' => $telegram_id
            ]);
            throw new \Exception($ownerDetails['problem'], 1);
        } elseif($oauth2state) {
            $db->perform(
                'UPDATE userdata '.
                'SET updated_at = now(), '.
                '    oauth2state = null '.
                'WHERE oauth2state = :oauth2state;', [
                    'telegram_id' => $telegram_id,
                    'oauth2state'    => $oauth2state
                ]);
        }
        return $ownerDetails;
    }
    
    /**
     * Get the access token from current user
     *
     * @param int $telegram_id
     * @param int $oauth2state
     * @throws \Exception
     * @return array Access Token
     */
    public function getAccessToken($telegram_id = null, $oauth2state = null)
    {
        $db = DB::getInstance();
        $query_factory = new QueryFactory($db->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $select = $query_factory->newSelect();
        $select
        ->cols([
            'token AS access_token',
            'oauth2state'
        ])
        ->from('userdata');
        $select->where('telegram_id = :telegram_id')
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
        if($oauth2state && $token['oauth2state'] != $oauth2state) {
            $db->perform('DELETE FROM userdata WHERE telegram_id = :telegram_id', [
                'telegram_id' => $telegram_id
            ]);
            throw new \Exception('Ocorreu um erro durante a autenticação, tente novamente.', 1);
        }
        unset($token['oauth2state']);
        $token = new AccessToken($token);
        return $token;
    }
}