<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\SlackAttachment;

class SlackNotification extends Notification
{
    use Queueable;

    protected $channel; // @var array 通知チャンネル情報
    protected $message; // @var string 通知メッセージ
    protected $attachment; // @var array 添付情報

    /**
     * Create a new notification instance.
     *
     * @param array $channel ['username' => string, 'icon' => string, 'title' => string]
     * @param string $message
     * @param array $attachment ['title' => string, 'content' => string, 'fields' => array]
     * @return void
     */
    public function __construct($channel = [], $message = '', $attachment = null)
    {
        $this->channel = $channel;
        $this->message = $message;
        $this->attachment = $attachment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
    }

    /**
     * Get the slack representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack($notifiable)
    {
        $message = (new SlackMessage)
            ->from($this->channel['username'], @$this->channel['icon'])
            ->to($this->channel['channel'])
            ->content($this->message);

        if (is_array($this->attachment)) {
            $message->attachment(function ($attachment) {
                if (isset($this->attachment['title'])) {
                    $attachment->title($this->attachment['title']);
                }
                if (isset($this->attachment['content'])) {
                    $attachment->content($this->attachment['content']);
                }
                if (is_array(@$this->attachment['field'])) {
                    foreach($this->attachment['field'] as $k => $v) {
                        $attachment->field($k, $v);
                    }
                }
            });
        }
        return $message;
    }
}
