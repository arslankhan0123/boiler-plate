<?php

declare(strict_types=1);

namespace App\Models\Projects;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    use UsesTenantConnection;

    protected $table = 'prj_task_comments';

    protected $fillable = ['task_id', 'user_id', 'user_name', 'body'];
}
