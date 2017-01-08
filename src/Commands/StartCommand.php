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
use Base\UserMeta;
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
        $UserMeta = new UserMeta();
        try {
            $ownerDetails = $UserMeta->getUser($telegram_id, $oauth2state);
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
}