<?php

namespace Demafelix\Auditor\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    protected $primaryKey = 'audit_trail_id';

    protected $fillable = [
        'user_id', 'model_name', 'model_entry_id', 'action', 'record'
    ];
}
