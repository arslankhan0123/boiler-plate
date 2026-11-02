<?php

declare(strict_types=1);

namespace App\Models\Settings;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'tax_rates';

    protected $fillable = ['name', 'rate', 'type', 'is_group', 'components', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['rate' => 'float', 'is_group' => 'boolean', 'components' => 'array', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    /** Component tax rows of a group (empty for a simple tax). */
    public function componentRows()
    {
        if (! $this->is_group || ! $this->components) {
            return collect();
        }

        return self::query()->whereIn('id', $this->components)->orderByDesc('rate')->get();
    }

    /**
     * Split a tax amount into this tax's components for display.
     * Simple tax → [name => amount]; group → each component by its share of the total rate.
     *
     * @return array<string, float>
     */
    public function split(float $taxAmount): array
    {
        if (! $this->is_group) {
            return [$this->name => round($taxAmount, 4)];
        }
        $rows = $this->componentRows();
        $sum = (float) $rows->sum('rate');
        if ($sum <= 0) {
            return [$this->name => round($taxAmount, 4)];
        }
        $out = [];
        foreach ($rows as $r) {
            $out[$r->name] = round($taxAmount * $r->rate / $sum, 4);
        }

        return $out;
    }
}
