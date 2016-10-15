<?php

namespace App\Entities\Repository;

use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

class CourseList extends Model implements Transformable
{
    use TransformableTrait;

    protected $fillable = [];

}
