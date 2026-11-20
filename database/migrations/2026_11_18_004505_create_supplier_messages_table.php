
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained()
                ->restrictOnDelete();

            // Replace sender_id with this:
            $table->foreignId('sender_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Replace recipient_id with this:
            $table->foreignId('recipient_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('subject', 150);
            $table->text('message');
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['supplier_id', 'created_at']);
            $table->index(['recipient_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_messages');
    }
};
