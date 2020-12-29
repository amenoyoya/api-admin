<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Amenoyoya\TrackableJob\Facades\TrackableJob;

class jobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:status {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        dump(TrackableJob::getJobStatus($this->argument('id')));
    }
}
