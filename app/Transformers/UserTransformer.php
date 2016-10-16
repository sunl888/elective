<?php

namespace App\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    /**
     * Related models to include in this transformation.
     *
     * @var array
     */
    protected $availableIncludes = [
        //
    ];

    /**
     * Turn this item object into a generic array.
     *
     * @param User $user
     * @return array
     */
    public function transform(User $user)
    {
        return [
            'id'=> $user->id,
            'number'=> $user->number,
            'name'=> $user->name,
            'class'=> $user->class,
            'selected_course'=> $user->selected_course,
            'introduce'=> $user->introduce,
            'custom'=> $user->costom,//判断是不是自定义的课程设计
            'course_id'=> $user->course_id,//用来修改课程设计内容
        ];
    }
}
