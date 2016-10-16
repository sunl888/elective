<?php

namespace App\Http\Controllers\Api;

use App\Models\Course;
use App\Models\User;
use App\Transformers\CourseListsTransformer;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomCourseController extends BaseApiController
{
    protected $validationRoles = [
        'course_name' => 'required|min:3',
        'introduce'=>'required|min:3',
    ];

    /**
     * 修改自定义的课程设计
     * input: course_id , course_name , introduce
     */
    public function upCourse(Request $request)
    {
        $custom = $request->input();
        $validator = Validator::make($custom , $this->validationRoles);
        if($validator->fails()){
            throw new StoreResourceFailedException('数据验证失败!', $validator->errors());
        }
        $course = Course::where([
            'id'=>$custom['course_id'],
            'custom'=>1//判断此题是不是用户自定义的题目,只有自定义的题目才可以修改
        ])->update([
            'course_name'=> $custom['course_name'],
            'introduce'=> $custom['introduce'],
        ]);
        dd($course);
        return $this->reponse();
    }

    /**
     * 添加自定义课程设计并且选择该课程设计
     * input: course_name , introduce
     */
    public function addCustomCourse(Request $request){
        $course = $request->input();
        $validator = Validator::make($request->input() , $this->validationRoles);
        if($validator->fails()){
            throw new StoreResourceFailedException('数据验证失败!', $validator->errors());
        }
        $user = JWTAuth::user();
        if($user->selected_course){
            throw new StoreResourceFailedException('你已经选择过课程设计,不可以再添加自定义课程.');
        }
        //判断添加的课程设计是否存在
        $lists = Course::where([
            'belong_class'=> $user->class,
            'course_name'=>$course['course_name'],
        ])->get();
        if(!$lists->isEmpty()){
            throw new \Dingo\Api\Exception\StoreResourceFailedException('该课程设计已经存在!');
        }

        $addCourse = Course::create([
            'id'=>null,
            'course_name'=> $request->course_name,
            'belong_class'=> $user->class,
            'introduce'=>$request->introduce,
            'status'=>1,
            'user_id'=>$user->id,
            'chooser'=>$user->name,
            'custom'=>1,
        ]);
        //dd($addCourse);
        $uInfo = User::find($user->id);
        $uInfo->selected_course = $addCourse->course_name;
        $uInfo->save();
        return $this->response->item($addCourse , new CourseListsTransformer());
    }
}
