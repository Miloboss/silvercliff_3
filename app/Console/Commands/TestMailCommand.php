<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify SMTP configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $recipient = $this->argument('email');
        
        $this->info("Attempting to send a test email to: {$recipient}...");

        try {
            Mail::raw('This is a test email from Silver Cliff Resort system to verify your SMTP settings.', function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('Silver Cliff - Mail Configuration Test');
            });

            $this->info("Success! Test email sent.");
            $this->comment("Check your inbox (and spam folder) at {$recipient}.");
        } catch (\Exception $e) {
            $this->error("Failed to send test email.");
            $this->error($e->getMessage());
            Log::error("Mail Test Command Failed: " . $e->getMessage());
        }
    }
}
