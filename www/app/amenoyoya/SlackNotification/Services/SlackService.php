<?php

namespace Amenoyoya\SlackNotification\Services;

use Illuminate\Notifications\Notifiable;
use Amenoyoya\SlackNotification\Notifications\SlackNotification;

class SlackService
{
    use Notifiable;

    /**
     * 通知チャンネル情報
     *
     * @var array
     */
    protected $channel = null;

    /**
     * 通知チャンネルを指定
     * 
     * @param string|array $channnel ['username' => string, 'icon' => string, 'title' => string]
     * @return this
     */
    public function channel($channel)
    {
        $this->channel = is_array($channel)? $channel: config('slack.channels.' . $channel);
        return $this;
    }

    /**
     * 通知処理
     *
     * @param string $message
     * @param array $attachment ['title' => string, 'content' => string, 'fields' => array]
     * @return void
     */
    public function send($message = '', $attachment = null)
    {
        if (!isset($this->channel)) {
            $this->channel(config('slack.default'));
        }

        $this->notify(new SlackNotification($this->channel, $message, $attachment));
    }

    /**
     * Slack通知用URLを指定する
     *
     * @return string
     */
    protected function routeNotificationForSlack()
    {
        $config = config('slack.url');
        // 設定が配列の場合はデータベースから読み込む: [query, args, column]
        if (is_array($config)) {
            $rows = \DB::select($config[0], $config[1]);
            return @$rows[0]->{$config[2]};
        }
        return $config;
    }
}