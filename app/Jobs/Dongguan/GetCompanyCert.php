<?php

namespace App\Jobs\Dongguan;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use QL\QueryList;
use Cache,DB,Log;

class GetCompanyCert implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $cert_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($cert_id)
    {
        $this->cert_id = $cert_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
