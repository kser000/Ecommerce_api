<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            DB::statement("UPDATE orders SET status = 'pending_payment' WHERE status = 'pending'");
            return;
        }

        // Step 1: 先把 pending_payment 加入 enum（保留 pending，讓現有資料仍合法）
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','pending_payment','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending_payment'");
        // Step 2: 把舊資料從 pending 改為 pending_payment（此時兩個值都合法）
        DB::statement("UPDATE orders SET status = 'pending_payment' WHERE status = 'pending'");
        // Step 3: 移除已無用的 pending
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending_payment','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending_payment'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            DB::statement("UPDATE orders SET status = 'pending' WHERE status = 'pending_payment'");
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','pending_payment','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("UPDATE orders SET status = 'pending' WHERE status = 'pending_payment'");
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
