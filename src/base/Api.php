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
            $provider = Meetup::getProvider();
            $token = UserMeta::getAccessToken($inlineQuery->getFrom()->getId());
            $offset = $inlineQuery->getOffset()?:0;
            $response = $provider->getAuthenticatedRequest(
                'GET',
                'https://api.meetup.com/2/open_events?&sign=true&photo-host=public'.
                '&text='.urlencode($query).
                '&page=10'.
                '&offset='.$offset,
                $token
            );
            $response = $provider->getResponse($response);
            if($response['meta']['total_count'] == 0) {
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
                foreach($response['results'] as $result) {
                    $items = [
                        'id' => $result['id'],
                        'title' => $result['name'],
                        'message_text' => $result['name'],
                        'description' => $result['name'],
                        'parse_mode' => 'HTML',
                        'disable_web_page_preview' => true
                    ];
                    try {
                        $photo = $provider->getAuthenticatedRequest(
                            'GET',
                            'https://api.meetup.com/'.$result['group']['urlname'].'?&sign=true&photo-host=public&only=group_photo',
                            $token
                        );
                        $photo = $provider->getResponse($photo);
                        $items['thumb_url'] = $photo['group_photo']['thumb_link'];
                    } catch(Exception $e) { }
                    $params['results'][] = InlineQueryResultArticle::make($items);
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