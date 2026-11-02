<?php

declare(strict_types=1);

namespace App\Models\Crm;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use Auditable, SoftDeletes, UsesTenantConnection;

    protected $table = 'crm_companies';

    protected $fillable = ['name', 'industry', 'website', 'phone', 'email', 'city', 'address', 'owner_id', 'notes', 'created_by'];

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function contacts(): HasMany { return $this->hasMany(Contact::class, 'company_id'); }
    public function opportunities(): HasMany { return $this->hasMany(Opportunity::class, 'company_id'); }
}
