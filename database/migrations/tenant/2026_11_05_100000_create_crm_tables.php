<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** CRM: companies, contacts, leads, opportunities (pipeline), activities, campaigns. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_companies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('industry', 100)->nullable();
            $t->string('website')->nullable();
            $t->string('phone', 40)->nullable();
            $t->string('email')->nullable();
            $t->string('city', 100)->nullable();
            $t->string('address')->nullable();
            $t->unsignedBigInteger('owner_id')->nullable(); // users.id (account manager)
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('crm_contacts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->string('name');
            $t->string('title', 120)->nullable();
            $t->string('email')->nullable();
            $t->string('phone', 40)->nullable();
            $t->unsignedBigInteger('owner_id')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('crm_leads', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('company')->nullable();
            $t->string('email')->nullable();
            $t->string('phone', 40)->nullable();
            $t->string('source', 60)->nullable();            // website, referral, call, ad…
            $t->string('status', 20)->default('new');        // new|contacted|qualified|unqualified|converted
            $t->decimal('value', 14, 2)->default(0);         // estimated value
            $t->unsignedBigInteger('owner_id')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('converted_contact_id')->nullable();
            $t->unsignedBigInteger('converted_opportunity_id')->nullable();
            $t->timestamp('converted_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('crm_opportunities', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->unsignedBigInteger('contact_id')->nullable()->index();
            $t->string('stage', 20)->default('qualification'); // qualification|proposal|negotiation|won|lost
            $t->decimal('amount', 14, 2)->default(0);
            $t->unsignedTinyInteger('probability')->default(10); // percent
            $t->date('close_date')->nullable();
            $t->string('source', 60)->nullable();
            $t->unsignedBigInteger('owner_id')->nullable();
            $t->text('notes')->nullable();
            $t->string('lost_reason')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('crm_activities', function (Blueprint $t) {
            $t->id();
            $t->string('type', 16)->default('task');  // call|email|meeting|task
            $t->string('subject');
            $t->dateTime('due_at')->nullable();
            $t->boolean('done')->default(false);
            $t->timestamp('done_at')->nullable();
            $t->string('related_type', 16)->nullable(); // lead|opportunity|contact|company
            $t->unsignedBigInteger('related_id')->nullable();
            $t->unsignedBigInteger('owner_id')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->index(['related_type', 'related_id']);
        });

        Schema::create('crm_campaigns', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('channel', 20)->default('email'); // email|sms|social|event|other
            $t->string('status', 16)->default('planned'); // planned|active|completed|cancelled
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->decimal('budget', 14, 2)->default(0);
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['crm_campaigns', 'crm_activities', 'crm_opportunities', 'crm_leads', 'crm_contacts', 'crm_companies'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
