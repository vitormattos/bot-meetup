<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Base\DB;
use League\OAuth2\Client\Token\AccessToken;
use Base\Meetup;
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
    public function handle($md5token)
    {
        if($md5token) {
            $db = DB::getInstance();
            $sth = $db->perform("SELECT token AS access_token FROM userdata WHERE md5token = :md5token;", [
                'md5token' => $md5token
            ]);
            $token = $sth->fetch(\PDO::FETCH_ASSOC);
            $message = $this->update->getMessage();
            if(!$token) {
                $text = 'Ocorreu um erro durante a autenticação, tente novamente.';
            } else {
                $provider = Meetup::getProvider();
                $accessToken = new AccessToken($token);
                $ownerDetails = $provider->getResourceOwner($accessToken)->toArray();
                if(array_key_exists('problem', $ownerDetails)) {
                    $text = $ownerDetails['problem'];
                } else {
                    $db->perform(
                        'UPDATE userdata '.
                        'SET telegram_id = :telegram_id, '.
                        '    updated_at = now(), '.
                        '    md5token = null'.
                        'WHERE md5token = :md5token;', [
                        'telegram_id' => $message->getFrom()->getId(),
                        'md5token'    => $md5token
                    ]);
                    $text = 'Bem vindo '. $ownerDetails['name'] . '!';
                }
            }
            $this->replyWithMessage([
                'chat_id' => $message->getChat()->getId(),
                'text' => $text,
            ]);
        }
    }
}