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
            $sth = $db->perform("SELECT token AS access_token FROM user WHERE md5token = :md5token;", [
                'md5token' => $md5token
            ]);
            $token = $sth->fetch(\PDO::FETCH_ASSOC);
            error_log(print_r($token, true));
            $provider = Meetup::getProvider();
            $accessToken = new AccessToken($token);
            $ownerDetails = $provider->getResourceOwner($accessToken);
            error_log(print_r($ownerDetails, true));
            $response = $this->replyWithMessage([
                'chat_id' => $this->update->getMessage()->getChat()->getId(),
                'text' => 'Bem vindo '. $ownerDetails->getName() . '!',
            ]);
        }
    }
}