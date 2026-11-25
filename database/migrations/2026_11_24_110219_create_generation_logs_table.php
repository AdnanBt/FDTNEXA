
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generation_logs', function (Blueprint $table) {
            $table->id();

            // User who generated the file
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // field_tool or bulk_generator
            $table->string('tool_type');

            // Number of generated reports or layers
            $table->unsignedInteger('quantity')->default(1);

            $table->timestamps();

            // Useful for user-specific dashboard statistics
            $table->index(['user_id', 'tool_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_logs');
    }
};
