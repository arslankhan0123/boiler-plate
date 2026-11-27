<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Interview assignment: an employee interviews the candidate and leaves feedback. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_job_applications', function (Blueprint $t) {
            $t->unsignedBigInteger('interviewer_id')->nullable()->after('interview_at')->index(); // employees.id
            $t->string('interview_mode', 20)->nullable()->after('interviewer_id');              // onsite|online|phone
            $t->string('interview_location')->nullable()->after('interview_mode');              // room / meeting link
            $t->string('interview_result', 20)->nullable()->after('interview_location');        // pass|fail|hold|no_show
            $t->unsignedTinyInteger('interview_rating')->nullable()->after('interview_result');
            $t->text('interview_feedback')->nullable()->after('interview_rating');
            $t->timestamp('feedback_at')->nullable()->after('interview_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_job_applications', function (Blueprint $t) {
            $t->dropColumn(['interviewer_id', 'interview_mode', 'interview_location', 'interview_result', 'interview_rating', 'interview_feedback', 'feedback_at']);
        });
    }
};
