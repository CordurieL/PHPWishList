<?php
declare(strict_types=1);

namespace mywishlist\models;

class Item extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'item';
    protected $primaryKey = 'id' ;
    public $timestamps = false ;

    public function liste()
    {
        return $this->belongsTo('mywishlist\models\Liste', 'liste_id') ;
    }

    public function participations()
    {
        return $this->hasMany('mywishlist\models\Participation', 'item_id') ;
    }
}
