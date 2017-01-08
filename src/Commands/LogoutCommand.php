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
    protected $name = 'logout';

    /**
     * @var string Command Description
     */
    protected $description = 'Desloga do Meetup';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $message = $this->update->getMessage();
        $telegram_id = $message->getFrom()->getId();
        $db->perform('DELETE FROM userdata WHERE telegram_id = :telegram_id', [
            'telegram_id' => $telegram_id
        ]);
        $this->replyWithMessage([
            'chat_id' => $message->getChat()->getId(),
            'text' =>
                "Você deslogou com suceso.\n".
                "Faça /start para se autenticar-se novamente",
        ]);
    }
}