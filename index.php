<?php
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Base\UserMeta;

require 'vendor/autoload.php';

if(file_exists('.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
    class mockApi extends Api{
        public function getWebhookUpdates($emitUpdateWasReceivedEvent = true) {
            return new Update(json_decode(getenv('MOCK_JSON'), true));
        }
    }
    $telegram = new mockApi();
} else {
    error_log(file_get_contents('php://input'));
    $telegram = new Api();
}


// Classic commands
$telegram->addCommands([
    \Commands\HelpCommand::class,
    \Commands\StartCommand::class
]);

$update = $telegram->getWebhookUpdates();
if($update->has('message')) {
    $message = $update->getMessage();
    if($message->has('text')) {
        switch($text = $message->getText()) {
            case '/about':
                $telegram->sendMessage([
                    'chat_id' => $message->getChat()->getId(),
                    'text' => 'Sobre alguma coisa',
                    'reply_to_message_id' => $message->getMessageId()
                ]);
                break;
        }
    }
}

$update = $telegram->processCommand($update);