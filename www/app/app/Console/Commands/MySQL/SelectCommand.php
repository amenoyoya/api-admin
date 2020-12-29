<?php

namespace App\Console\Commands\MySQL;

use Illuminate\Console\Command;

class SelectCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql:select {query}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'MySQL select query execution';

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
        echo json_encode(\DB::select($this->argument('query'))) . "\n";
        return 0;
    }
}
