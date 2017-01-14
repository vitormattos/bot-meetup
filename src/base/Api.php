<?php
namespace Base;

use Telegram\Bot\Objects\InlineQuery\InlineQueryResultArticle;

class Api extends \Telegram\Bot\Api
{
    public function processInlineQuery(\Telegram\Bot\Objects\Update $update)
    {
        if(!$update->has('inline_query')) {
            return;
        }
        $inlineQuery = $update->getInlineQuery();
        if($query = $inlineQuery->getQuery()) {
            $offset = $inlineQuery->getOffset()?:0;

            $token = UserMeta::getAccessToken($telegram_id = $inlineQuery->getFrom()->getId());
            $provider = Meetup::getProvider();
            $provider->setToken($token->getToken());
            $response = $provider->getCommand('GET',
                '/2/open_events?&sign=true&photo-host=public'.
                '&text='.urlencode($query).
                '&page=5'.
                '&offset='.$offset,
                $token
            );
            if(isset($response['problem'])) {
                $params = [
                    'switch_pm_text' => 'Please, login...'
                ];
            } elseif($response['meta']['total_count'] == 0) {
                $params = [
                    'results' =>
                        [
                            InlineQueryResultArticle::make([
                                'id' => 'no-query',
                                'title' => 'No results',
                                'message_text' => 'No results',
                                'description' =>
                                    'Sorry! I found nothing with your search term. Try again.'
                            ])
                        ]
                    ];
            } else {
                if($response['meta']['next']) {
                    preg_match('/&offset=(?<offset>\d+)/', $response['meta']['next'], $next_offset);
                    $params['next_offset'] = $next_offset['offset'];
                } else {
                    $params['next_offset'] = '';
                }
                $events = [];
                foreach($response['results'] as $result) {
                    $events[] = [
                        'group_id' => $result['group']['id'],
                        'id' => $result['id'],
                        'title' => $result['name'],
                        'message_text' => $result['name'],
                        'description' => $result['name'],
                        'parse_mode' => 'HTML',
                        'disable_web_page_preview' => true
                    ];
                    $commands[] = (object)[
                        'path' => '/'.$result['group']['urlname'],
                        'params' => [
                            'sign' => true,
                            'photo-host' => 'public',
                            'only'=> 'id,group_photo'
                        ]
                    ];
                }
                $return = $provider->getCommand('POST', '/batch',
                    'requests='.json_encode($commands)
                );
                foreach($return as $photo) {
                    if(isset($photo['body']['group_photo']))
                    foreach($events as $event_id => $event) {
                        if($event['group_id'] == $photo['body']['id']) {
                            $events[$event_id]['thumb_url'] =
                                $photo['body']['group_photo']['thumb_link'];
                        }
                    }
                }
                foreach($events as $event_id => $event) {
                    unset($event[$event_id]['group_id']);
                    $params['results'][] = InlineQueryResultArticle::make($event);
                }
            }
        } else {
            $params = [
                'switch_pm_text' => 'Type the query...',
                'switch_pm_parameter' => 'inline help'
            ];
        }
        $this->answerInlineQuery(
            [
                'inline_query_id' => $inlineQuery->getId(),
                'cache_time' => 0,
            ] +  $params
        );
    }
}