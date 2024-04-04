<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class YourCommand extends Command
{
    protected $signature = 'your:command';

    protected $description = 'Description of your command';

    public function handle()
    {
        $response = Http::get('http://emeteraidemo.ifca.co.id/approval_btid/api/autosend');
        
        // Handle the response or any errors
    }
}
