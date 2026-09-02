<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_centers', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        $empresa = Company::firstOrCreate(
            ['nombre' => 'Empresa Principal'],
            ['direccion' => null]
        );

        DB::table('work_centers')
            ->whereNull('company_id')
            ->update(['company_id' => $empresa->id]);
    }

    public function down(): void
    {
        Schema::table('work_centers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
