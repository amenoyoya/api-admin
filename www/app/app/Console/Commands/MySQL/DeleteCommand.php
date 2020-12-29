<?php

namespace App\Console\Commands\MySQL;

use Illuminate\Console\Command;

class DeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql:delete {query}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'MySQL delete query execution';

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
        \DB::delete($this->argument('query'));
        return 0;
    }
}
