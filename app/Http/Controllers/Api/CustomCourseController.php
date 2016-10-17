<?php

namespace App\Http\Controllers\Api;

use App\Models\Course;
use App\Models\User;
use App\Transformers\CourseListsTransformer;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Requests;
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
     * 1.只有自己定义的题目才可以修改
     * return reource | array[] | exception
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
            'is_custom'=>1//判断此题是不是用户自定义的题目,只有自定义的题目才可以修改
        ])->first();
        $course->course_name = $custom['course_name'];
        $course->introduce = $custom['introduce'];
        $user = JWTAuth::user();
        $uInfo = User::find($user->id);
        $uInfo->selected_course = $course->course_name;
        if(!$course->save() || !$uInfo->save()){
            throw new StoreResourceFailedException('修改课程设计失败');
        }
        return $this->response->item($course , new CourseListsTransformer());
    }

    /**
     * 添加自定义课程设计并且选择该课程设计
     * input: course_name , introduce
     * 1.当老师提供的课程设计不符合要求或者不够的时候学生可以自己添加喜欢的课程设计
     * 2.自己添加的课程设计只有自己才可以修改使用is_custom来区分是不是自己定义
     */
    public function addCustomCourse(Request $request){
        $course = $request->all();
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
        $data = [
            'id'=>null,
            'course_name'=> $request->course_name,
            'belong_class'=> $user->class,
            'introduce'=> $request->introduce,
            'status'=> 1,
            'user_id'=> $user->id,
            'chooser'=> $user->name,
            'is_custom'=> 1,
        ];
        $addCourse = Course::create($data);

        $uInfo = User::find($user->id);
        $uInfo->selected_course = $addCourse->course_name;
        $uInfo->save();
        return $this->response->item($addCourse , new CourseListsTransformer());
    }
}
