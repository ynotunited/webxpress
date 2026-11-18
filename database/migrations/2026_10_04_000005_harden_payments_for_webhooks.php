<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hardening the payments table for money arriving from outside.
 *
 * Two of these are correctness constraints rather than columns, and both close
 * a way to take the same money twice.
 *
 * `paystack_reference` becomes unique. The workflow reuses one Payment row per
 * deal phase and issues a fresh reference each time a link is generated, so
 * pressing "Generate payment link" twice used to leave two live Paystack
 * transactions pointing at one invoice. A client could settle either, and the
 * second had nothing to stop it. The uniqueness is on the gateway's own
 * identifier because that is the only value that can prove two requests are the
 * same charge.
 *
 * `(deal_id, kind)` becomes unique. It was only enforced in PHP, by a lookup
 * that two concurrent requests could both pass. The database is the only place
 * that can make that check true under concurrency.
 *
 * The gateway_* columns record what Paystack actually asserted about the charge
 * alongside what the site expected. Without them, a mismatch is unreconstructable
 * after the fact: the payments table would just say "paid" with no record of the
 * amount the gateway reported.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->releaseDuplicateReferences();

        Schema::table('payments', function (Blueprint $table) {
            // What the gateway said, kept next to what we expected.
            $table->unsignedBigInteger('gateway_amount')->nullable()->after('amount');
            $table->string('gateway_currency', 3)->nullable()->after('gateway_amount');
            $table->string('gateway_status', 32)->nullable()->after('gateway_currency');
            $table->timestamp('gateway_charged_at')->nullable()->after('gateway_status');
            $table->string('gateway_authorization_code', 100)->nullable()->after('gateway_charged_at');

            // How this payment came to be known as paid: webhook | callback |
            // reconcile. When a client disputes a charge, which of these fired
            // first is the first thing to establish.
            $table->string('confirmed_via', 16)->nullable()->after('verified_at');

            // Why a payment was refused despite money arriving.
            $table->string('failure_reason', 255)->nullable()->after('confirmed_via');
        });

        // MySQL and SQLite both treat UNIQUE as case-insensitive on the default
        // collation, which is what we want: Paystack references are uppercase,
        // and a lowercase duplicate must collide rather than slip past.
        Schema::table('payments', function (Blueprint $table) {
            $table->unique('paystack_reference', 'payments_paystack_reference_unique');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unique(['deal_id', 'kind'], 'payments_deal_id_kind_unique');
        });
    }

    /**
     * A unique index cannot be added while duplicates exist, and this migration
     * has to run against a production table that nobody has inspected.
     *
     * A duplicate reference is corrupt regardless — two invoices cannot share
     * one gateway identifier — so the row to keep is the one with evidence
     * behind it: a paid payment wins over a pending one, and otherwise the
     * earliest row wins. The losers are demoted to pending with their reference
     * released rather than deleted, so nothing is destroyed and they can be
     * re-issued deliberately.
     */
    protected function releaseDuplicateReferences(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'paystack_reference')) {
            return;
        }

        $duplicates = DB::table('payments')
            ->whereNotNull('paystack_reference')
            ->select('paystack_reference', DB::raw('MIN(id) as keep_id'))
            ->groupBy('paystack_reference')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $keep = DB::table('payments')
                ->where('paystack_reference', $duplicate->paystack_reference)
                ->orderByRaw("CASE WHEN status = 'paid' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->first();

            if ($keep === null) {
                continue;
            }

            DB::table('payments')
                ->where('paystack_reference', $duplicate->paystack_reference)
                ->where('id', '!=', $keep->id)
                ->update([
                    'paystack_reference' => null,
                    'status' => 'pending',
                    'failure_reason' => 'Released a duplicate Paystack reference during a schema upgrade.',
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_deal_id_kind_unique');
            $table->dropUnique('payments_paystack_reference_unique');

            $table->dropColumn([
                'gateway_amount',
                'gateway_currency',
                'gateway_status',
                'gateway_charged_at',
                'gateway_authorization_code',
                'confirmed_via',
                'failure_reason',
            ]);
        });
    }
};