<?php

namespace Modules\AgeingReport\Entities;

use Illuminate\Database\Eloquent\Model;

class AgeingReport extends Model
{
    protected $guarded = ['id']; 
    protected $table = 'inventories_reset';
    /**
     * user added.
     */
    public function createdBy()
    {
        return $this->belongsTo('App\User', 'created_by');
    }    
    public function product(){
        return $this->hasMany("Modules\AgeingReport\Entities\AgeingReportProducts",'inventories_reset_id','id')
        ->distinct();
    }
}
