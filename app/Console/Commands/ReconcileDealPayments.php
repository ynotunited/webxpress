<?php

namespace App\Console\Commands;

use App\Services\DealWorkflowService;
use Illuminate\Console\Command;

class ReconcileDealPayments extends Command
{
    protected $signature = 'deals:reconcile-payments';

    protected $description = 'Verify pending deal payments against Paystack and mark any successful ones as paid';

    public function handle(DealWorkflowService $workflow): int
    {
        $count = $workflow->reconcilePendingPayments();

        $this->info("Reconciled pending payments: {$count} marked as paid.");

        return self::SUCCESS;
    }
}
