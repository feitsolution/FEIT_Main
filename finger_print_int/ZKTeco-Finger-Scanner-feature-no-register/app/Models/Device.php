<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

      public function branchMapping()
    {
        return $this->hasOne(
            BranchMapping::class,
            'serial_number', // branch_mapping column
            'no_sn'          // devices column
        );
    }
}
