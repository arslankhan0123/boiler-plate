<?php

use App\Http\Controllers\Crm\ActivityController;
use App\Http\Controllers\Crm\CampaignController;
use App\Http\Controllers\Crm\CompanyController;
use App\Http\Controllers\Crm\ContactController;
use App\Http\Controllers\Crm\HistoryController;
use App\Http\Controllers\Crm\LeadController;
use App\Http\Controllers\Crm\OpportunityController;
use Illuminate\Support\Facades\Route;

/* CRM — module:crm on each group; permissions checked inside controllers. */
Route::prefix('crm')->name('crm.')->group(function () {

    // Leads
    Route::prefix('leads')->name('leads.')->middleware('module:crm,leads')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('index');
        Route::get('create', [LeadController::class, 'create'])->name('create');
        Route::post('/', [LeadController::class, 'store'])->name('store');
        Route::get('{lead}', [LeadController::class, 'show'])->whereNumber('lead')->name('show');
        Route::get('{lead}/edit', [LeadController::class, 'edit'])->whereNumber('lead')->name('edit');
        Route::put('{lead}', [LeadController::class, 'update'])->whereNumber('lead')->name('update');
        Route::delete('{lead}', [LeadController::class, 'destroy'])->whereNumber('lead')->name('destroy');
        Route::patch('{id}/restore', [LeadController::class, 'restore'])->whereNumber('id')->name('restore');
        Route::post('{lead}/convert', [LeadController::class, 'convert'])->whereNumber('lead')->name('convert');
    });

    // Companies
    Route::prefix('companies')->name('companies.')->middleware('module:crm,contacts')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('index');
        Route::get('create', [CompanyController::class, 'create'])->name('create');
        Route::post('/', [CompanyController::class, 'store'])->name('store');
        Route::get('{company}', [CompanyController::class, 'show'])->whereNumber('company')->name('show');
        Route::get('{company}/edit', [CompanyController::class, 'edit'])->whereNumber('company')->name('edit');
        Route::put('{company}', [CompanyController::class, 'update'])->whereNumber('company')->name('update');
        Route::delete('{company}', [CompanyController::class, 'destroy'])->whereNumber('company')->name('destroy');
        Route::patch('{id}/restore', [CompanyController::class, 'restore'])->whereNumber('id')->name('restore');
    });

    // Contacts
    Route::prefix('contacts')->name('contacts.')->middleware('module:crm,contacts')->group(function () {
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::get('create', [ContactController::class, 'create'])->name('create');
        Route::post('/', [ContactController::class, 'store'])->name('store');
        Route::get('{contact}/edit', [ContactController::class, 'edit'])->whereNumber('contact')->name('edit');
        Route::put('{contact}', [ContactController::class, 'update'])->whereNumber('contact')->name('update');
        Route::delete('{contact}', [ContactController::class, 'destroy'])->whereNumber('contact')->name('destroy');
        Route::patch('{id}/restore', [ContactController::class, 'restore'])->whereNumber('id')->name('restore');
    });

    // Opportunities / pipeline
    Route::prefix('opportunities')->name('opportunities.')->middleware('module:crm,pipeline')->group(function () {
        Route::get('/', [OpportunityController::class, 'index'])->name('index');
        Route::get('create', [OpportunityController::class, 'create'])->name('create');
        Route::post('/', [OpportunityController::class, 'store'])->name('store');
        Route::get('{opportunity}', [OpportunityController::class, 'show'])->whereNumber('opportunity')->name('show');
        Route::get('{opportunity}/edit', [OpportunityController::class, 'edit'])->whereNumber('opportunity')->name('edit');
        Route::put('{opportunity}', [OpportunityController::class, 'update'])->whereNumber('opportunity')->name('update');
        Route::patch('{opportunity}/stage', [OpportunityController::class, 'stage'])->whereNumber('opportunity')->name('stage');
        Route::delete('{opportunity}', [OpportunityController::class, 'destroy'])->whereNumber('opportunity')->name('destroy');
    });

    // Activities & follow-ups
    Route::prefix('activities')->name('activities.')->middleware('module:crm,activities')->group(function () {
        Route::get('/', [ActivityController::class, 'index'])->name('index');
        Route::post('/', [ActivityController::class, 'store'])->name('store');
        Route::put('{activity}', [ActivityController::class, 'update'])->whereNumber('activity')->name('update');
        Route::patch('{activity}/toggle', [ActivityController::class, 'toggle'])->whereNumber('activity')->name('toggle');
        Route::delete('{activity}', [ActivityController::class, 'destroy'])->whereNumber('activity')->name('destroy');
    });

    // Campaigns
    Route::prefix('campaigns')->name('campaigns.')->middleware('module:crm,campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::get('create', [CampaignController::class, 'create'])->name('create');
        Route::post('/', [CampaignController::class, 'store'])->name('store');
        Route::get('{campaign}/edit', [CampaignController::class, 'edit'])->whereNumber('campaign')->name('edit');
        Route::put('{campaign}', [CampaignController::class, 'update'])->whereNumber('campaign')->name('update');
        Route::delete('{campaign}', [CampaignController::class, 'destroy'])->whereNumber('campaign')->name('destroy');
    });

    // Customer history (read-only)
    Route::prefix('history')->name('history.')->middleware('module:crm,history')->group(function () {
        Route::get('/', [HistoryController::class, 'index'])->name('index');
        Route::get('{contact}', [HistoryController::class, 'show'])->whereNumber('contact')->name('show');
    });
});
