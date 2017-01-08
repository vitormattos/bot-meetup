<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Base\DB;
use League\OAuth2\Client\Token\AccessToken;
use Base\Meetup;
use Telegram\Bot\Keyboard\Keyboard;
use Aura\SqlQuery\QueryFactory;
use Aura\Sql\Exception;
use Telegram\Bot\Helpers\Emojify;
/**
 * Class StartCommand.
 */
class StartCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'start';

    /**
     * @var string Command Description
     */
    protected $description = 'Inicia o uso do bot';

    /**
     * {@inheritdoc}
     */
    public function handle($oauth2state)
    {
        $message = $this->update->getMessage();
        $telegram_id = $message->getFrom()->getId();
        try {
            $ownerDetails = $this->getUser($telegram_id, $oauth2state);
            $this->replyWithMessage([
                'chat_id' => $message->getChat()->getId(),
                'text' => 'Bem vindo '. $ownerDetails['name'] . '!',
            ]);
        } catch(\Exception $e) {
            $provider = Meetup::getProvider();

            $reply_markup = Keyboard::make();
            $reply_markup->inline();
            $reply_markup->row(
                Keyboard::inlineButton([
                    'text' => Emojify::text(':link:').'Faça login no Meetup.com',
                    'url' => $provider->getAuthorizationUrl()
                ])
            );

            $db = DB::getInstance();
            $sth = $db->perform('INSERT INTO userdata (oauth2state, telegram_id) VALUES(:oauth2state, :telegram_id);', [
                'oauth2state' => md5($provider->getState()),
                'telegram_id' => $telegram_id
            ]);

            $this->replyWithMessage([
                'chat_id' => $message->getChat()->getId(),
                'text' => $e->getCode()
                    ? $e->getMessage()
                    : 'Ocorreu um erro durante a autenticação, tente novamente',
                'disable_web_page_preview' => true,
                'reply_markup' => $reply_markup
            ]);
        }
    }

    /**
     * 
     * @param int $telegram_id
     * @param int $oauth2state
     * @throws \Exception
     */
    private function getUser($telegram_id = null, $oauth2state = null)
    {
        $token = $this->getAccessToken($telegram_id, $oauth2state);
        $provider = Meetup::getProvider();
        $db = DB::getInstance();
        $accessToken = new AccessToken($token);
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
    private function getAccessToken($telegram_id = null, $oauth2state = null)
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
        if(!$token['access_token']
            || ($oauth2state && $token['oauth2state'] != $oauth2state)) {
            $db->perform('DELETE FROM userdata WHERE telegram_id = :telegram_id', [
                'telegram_id' => $telegram_id
            ]);
            throw new \Exception('Ocorreu um erro durante a autenticação, tente novamente.', 1);
        }
        unset($token['oauth2state']);
        return $token;
    }
}