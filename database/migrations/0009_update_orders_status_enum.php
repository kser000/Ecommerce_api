<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite 不支援 MODIFY COLUMN 也不使用 enum，直接跳過
        if (DB::getDriverName() !== 'mysql') {
            DB::statement("UPDATE orders SET status = 'pending_payment' WHERE status = 'pending'");
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending_payment','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending_payment'");
        DB::statement("UPDATE orders SET status = 'pending_payment' WHERE status = 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            DB::statement("UPDATE orders SET status = 'pending' WHERE status = 'pending_payment'");
            return;
        }

        DB::statement("UPDATE orders SET status = 'pending' WHERE status = 'pending_payment'");
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
