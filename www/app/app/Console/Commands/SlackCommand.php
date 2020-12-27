<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SlackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slack:send {message}
        {--T|target= : Target channel name ("default"|"notice"|"error")}
        {--C|channel= : Target slack channel (Valid only if no target channel name is specified)}
        {--U|username= : Notification sender user name (Valid only if no target channel name is specified)}
        {--I|icon= : Notification sender icon (Valid only if no target channel name is specified)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Slack notification';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $target = $this->option('target');
        $channel = $this->option('channel');
        $username = $this->option('username') ?: 'slackbot';
        $icon = $this->option('icon') ?: ':ghost:';
        if ($target === null && $channel !== null) {
            $target = [
                'channel' => $channel,
                'username' => $username,
                'icon' => $icon,
            ];
        }
        if ($target !== null) {
            \Slack::channel($target)->send($this->argument('message'));
            return 0;
        }
        \Slack::send($this->argument('message'));
        return 0;
    }
}
