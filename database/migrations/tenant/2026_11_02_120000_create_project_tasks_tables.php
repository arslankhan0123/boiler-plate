<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Task board (Projects module) inside each tenant database. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prj_tasks', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('status', 20)->default('todo')->index();     // todo|in_progress|review|done
            $t->string('priority', 10)->default('medium');          // low|medium|high|urgent
            $t->date('due_date')->nullable();
            $t->unsignedBigInteger('assignee_id')->nullable()->index(); // employees.id
            $t->unsignedBigInteger('created_by')->nullable();          // central users.id
            $t->string('created_by_name')->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('prj_task_comments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('task_id')->index();
            $t->unsignedBigInteger('user_id')->nullable(); // central users.id
            $t->string('user_name');
            $t->text('body');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prj_task_comments');
        Schema::dropIfExists('prj_tasks');
    }
};
