<?php

declare(strict_types=1);

namespace App\Models\Crm;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use Auditable, SoftDeletes, UsesTenantConnection;

    protected $table = 'crm_contacts';

    protected $fillable = ['company_id', 'name', 'title', 'email', 'phone', 'owner_id', 'notes', 'created_by'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class, 'company_id'); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
}
