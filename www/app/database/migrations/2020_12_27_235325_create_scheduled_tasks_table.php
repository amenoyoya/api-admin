<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduledTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('schedule')->comment('cronスケジュール');
            $table->text('command')->comment('実行コマンド');
            $table->enum('type', ['artisan', 'system'])->comment('実行タイプ');
            $table->text('memo')->nullable()->comment('備考');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scheduled_tasks');
    }
}
