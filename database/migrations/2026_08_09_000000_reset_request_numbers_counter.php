<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceRequest;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Temporarily clear request_number on all rows to prevent unique key conflict
        DB::table('service_requests')->update(['request_number' => null]);

        // 2. Order all existing service requests by id ascending and assign sequential numbers
        $requests = ServiceRequest::orderBy('id', 'asc')->get();
        
        $counter = 1;
        foreach ($requests as $req) {
            $formattedNumber = sprintf('SR-2026-%05d', $counter);
            DB::table('service_requests')
                ->where('id', $req->id)
                ->update([
                    'request_number' => $formattedNumber,
                    'display_number' => $counter,
                ]);
            $counter++;
        }

        // 3. Reset request_sequences table counters so the NEXT created request continues cleanly
        DB::table('request_sequences')
            ->where('prefix', 'SR')
            ->where('year', 2026)
            ->update(['last_number' => max(0, $counter - 1)]);

        DB::table('request_sequences')
            ->where('prefix', 'NUM')
            ->where('year', 0)
            ->update(['last_number' => max(0, $counter - 1)]);
    }

    public function down(): void
    {
        // No-op
    }
};
