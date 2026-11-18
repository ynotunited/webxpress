<?php

namespace App\Jobs;

use App\Mail\DealOnboarding;
use App\Models\Deal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOnboarding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public Deal $deal,
    ) {}

    public function handle(): void
    {
        if ($this->deal->status !== Deal::STATUS_IN_PROGRESS) {
            return;
        }

        Mail::to($this->deal->email)
            ->send(new DealOnboarding($this->deal));
    }
}
