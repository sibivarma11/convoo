<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\Message;
use Illuminate\Support\Facades\Log;

class ProcessMedia implements ShouldQueue
{
    use Queueable;

    public $message;

    /**
     * Create a new job instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Processing media for message {$this->message->id}");

        // Placeholder for FFmpeg logic:
        // if ($this->message->type === 'video') {
        //     // Generate thumbnail
        // } elseif ($this->message->type === 'audio') {
        //     // Get duration
        // }
    }
}
