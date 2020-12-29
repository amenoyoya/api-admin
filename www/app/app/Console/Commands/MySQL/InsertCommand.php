<?php

namespace App\Console\Commands\MySQL;

use Illuminate\Console\Command;

class InsertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql:insert {query}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'MySQL insert query execution';

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
        \DB::insert($this->argument('query'));
        return 0;
    }
}
