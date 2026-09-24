<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('import_funds')) {
            Schema::create('import_funds', function (Blueprint $table) {
                $table->id();
                $table->string('memberid', 50)->nullable()->index();
                $table->string('user_id', 30)->nullable()->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->string('txnid', 255)->nullable()->index();
                $table->string('transaction_hash', 255)->nullable()->index();
                $table->string('orderid', 100)->nullable()->index();
                $table->decimal('amount', 16, 4)->default(0.0000);
                $table->string('type', 50)->default('credit');
                $table->string('wallet_type', 50)->default('p2p_wallet');
                $table->string('wallet_address', 255)->nullable();
                $table->string('network', 50)->default('BEP-20');
                $table->string('token', 50)->default('USDT');
                $table->string('contract_address', 255)->nullable();
                $table->string('added_by', 50)->nullable();
                $table->string('status', 50)->default('Pending')->index();
                $table->string('verification_status', 50)->default('unverified')->index();
                $table->string('deposit_status', 50)->default('pending')->index();
                $table->longText('verification_payload')->nullable();
                $table->text('admin_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->unsignedBigInteger('verified_by')->nullable()->index();
                $table->string('mode', 50)->default('dapp_web3');
                $table->timestamps();
            });
        } else {
            Schema::table('import_funds', function (Blueprint $table) {
                if (!Schema::hasColumn('import_funds', 'user_id')) {
                    $table->string('user_id', 30)->nullable()->after('memberid')->index();
                }
                if (!Schema::hasColumn('import_funds', 'member_id')) {
                    $table->unsignedBigInteger('member_id')->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('import_funds', 'txnid')) {
                    $table->string('txnid', 255)->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('import_funds', 'transaction_hash')) {
                    $table->string('transaction_hash', 255)->nullable()->after('txnid')->index();
                }
                if (!Schema::hasColumn('import_funds', 'wallet_address')) {
                    $table->string('wallet_address', 255)->nullable()->after('wallet_type');
                }
                if (!Schema::hasColumn('import_funds', 'network')) {
                    $table->string('network', 50)->default('BEP-20')->after('wallet_address');
                }
                if (!Schema::hasColumn('import_funds', 'token')) {
                    $table->string('token', 50)->default('USDT')->after('network');
                }
                if (!Schema::hasColumn('import_funds', 'contract_address')) {
                    $table->string('contract_address', 255)->nullable()->after('token');
                }
                if (!Schema::hasColumn('import_funds', 'verification_status')) {
                    $table->string('verification_status', 50)->default('unverified')->after('status')->index();
                }
                if (!Schema::hasColumn('import_funds', 'deposit_status')) {
                    $table->string('deposit_status', 50)->default('pending')->after('verification_status')->index();
                }
                if (!Schema::hasColumn('import_funds', 'verification_payload')) {
                    $table->longText('verification_payload')->nullable()->after('deposit_status');
                }
                if (!Schema::hasColumn('import_funds', 'admin_notes')) {
                    $table->text('admin_notes')->nullable()->after('verification_payload');
                }
                if (!Schema::hasColumn('import_funds', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('admin_notes');
                }
                if (!Schema::hasColumn('import_funds', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('rejection_reason');
                }
                if (!Schema::hasColumn('import_funds', 'verified_by')) {
                    $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at')->index();
                }
            });
        }

        // Create or replace view `import_fund` pointing to `import_funds` for singular naming compatibility
        try {
            DB::statement("CREATE OR REPLACE VIEW import_fund AS SELECT * FROM import_funds");
        } catch (\Throwable $e) {
            // In environments without view creation privilege, continue gracefully
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('import_funds')) {
            Schema::table('import_funds', function (Blueprint $table) {
                $columns = [
                    'user_id',
                    'member_id',
                    'transaction_hash',
                    'wallet_address',
                    'network',
                    'token',
                    'contract_address',
                    'verification_status',
                    'deposit_status',
                    'verification_payload',
                    'admin_notes',
                    'rejection_reason',
                    'verified_at',
                    'verified_by',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('import_funds', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });

            try {
                DB::statement("DROP VIEW IF EXISTS import_fund");
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }
};
