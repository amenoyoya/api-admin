<?php

namespace App\Console\Commands\MySQL;

use Illuminate\Console\Command;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql:update {query}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'MySQL update query execution';

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
        \DB::update($this->argument('query'));
        return 0;
    }
}
