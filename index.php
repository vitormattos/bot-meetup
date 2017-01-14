<?php
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

require 'vendor/autoload.php';

if(file_exists('.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
    class mockApi extends \Base\Api{
        public function getWebhookUpdate($emitUpdateWasReceivedEvent = true) {
            return new Update(json_decode(getenv('MOCK_JSON'), true));
        }
    }
    $telegram = new mockApi();
} else {
    error_log(file_get_contents('php://input'));
    $telegram = new \Base\Api();
}

// Classic commands
$telegram->addCommands([
    \Commands\HelpCommand::class,
    \Commands\StartCommand::class,
    \Commands\LogoutCommand::class,
    \Commands\AboutCommand::class
]);

$update = $telegram->getWebhookUpdate();
foreach(['InlineQuery', 'Command'] as $method) {
    call_user_func([$telegram, 'process'.$method], $update);
    if($telegram->getLastResponse()) {
        break;
    }
}

// $token = \Base\UserMeta::getAccessToken($update->getMessage()->getFrom()->getId());
// $client = \Base\MeetupOAuthClient::factory(['token' => $token->getToken()]);
// $command = $client->getEvents([
//         'group_urlname' => 'php-rio',
//         'status' => 'past,upcoming',
//         'desc' => 'desc'
// ]);
// var_dump($command);
// exit;
// $request = $provider->getAuthenticatedRequest('GET', 'https://api.meetup.com/2/member/self', $token);
// $request = $provider->getAuthenticatedRequest('GET', 'https://api.meetup.com/find/events', $token);
// $request = $provider->getAuthenticatedRequest('GET', 'https://api.meetup.com/2/open_events?&sign=true&photo-host=public&text=phpsp&page=1&offset=3', $token);
// $provider = \Base\Meetup::getProvider();
// $provider->setToken($token->getToken());
// $return = $provider->getCommand('GET', '/2/member/self');
// $return = $provider->getCommand('GET', '/find/events');
// $return = $provider->getCommand('GET', '/2/open_events?&sign=true&photo-host=public&text=phpsp&page=2&offset=1');
// $return = $provider->getCommand('POST', '/batch',
//     'requests='.json_encode([
//         (object)[
//             'path' => '/2/open_events',
//             'params' => (object)[
//                 'sign'=>true,
//                 'photo-host'=>'public',
//                 'page'=>10
//             ]
//         ],
//         (object)[
//             'path' => '/2/member/self'
//         ]
//     ])
// );
// print_r($return);