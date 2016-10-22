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
    public function upCourse(Request $request , $id)
    {
        $custom = $request->input();
        $validator = Validator::make($custom , $this->validationRoles , [
            'required'=>':attribute 必须要填写.',
            'min'=>':attribute 长度不得小于3',
        ],[
            'course_name'=> '课程名',
            'introduce'=> '课程简介',
        ]);
        if($validator->fails()){
            throw new StoreResourceFailedException('数据验证失败!', $validator->errors());
        }
        $course = Course::where([
            'id'=>$id,
            'is_custom'=>1//判断此题是不是用户自定义的题目,只有自定义的题目才可以修改
        ])->first();
        if($course == null){
            throw new StoreResourceFailedException('这个题目不是你自定义的,不可以修改.');
        }
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
        $validator = Validator::make($request->input() , $this->validationRoles , [
            'required'=>':attribute 必须要填写.',
            'min'=>':attribute 长度不得小于3',
        ],[
            'course_name'=> '课程名',
            'introduce'=> '课程简介',
        ]);
        if($validator->fails()){
            throw new StoreResourceFailedException('数据验证失败!', $validator->errors());
        }
        //将用户之前选择的题目取消
        $user = JWTAuth::user();
        $course = Course::where(['user_id' => $user->id])->first();
        if($course != null){
            if($course->is_custom){
                $course->delete();
            }else{
                $course->chooser = null;
                $course->status = 0;
                $course->user_id =null;
                if(!$course->save()){
                    throw new StoreResourceFailedException('自定义选题失败.');
                }
            }
        }
        //判断添加的课程设计是否存在
        $lists = Course::where([
            'belong_class'=> $user->class,
            'course_name'=>$request['course_name'],
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
