<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Course;
use App\Transformers\UserTransformer;
use Illuminate\Http\Request;
use Mockery\CountValidator\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Transformers\CourseListsTransformer;
use Dingo\Api\Exception\StoreResourceFailedException;

class AdminController extends BaseApiController
{
    //验证规则
    protected $validationRoles = [
        'course_name' => 'required|min:3',
        'introduce'=>'required|min:3',
    ];
    protected $user = null;

    public function __construct()
    {
        $this->user = JWTAuth::user();
    }

    /**
     * 导入本班所有的课程设计题目
     */
    public function addCourse(Request $request){
        $validator = Validator::make($request->input() , $this->validationRoles , [
            'required'=>':attribute必须要填写.',
            'min'=>':attribute长度不得小于3',
        ],[
            'course_name'=> '课程名',
            'introduce'=> '课程简介',
        ]);
        if($validator->fails()){
            throw new Exception('数据验证失败!'.$validator->errors()->first());
        }

        //判断添加的课程设计是否存在
        if($this->existedCourse($request->course_name)){
            throw new \Dingo\Api\Exception\StoreResourceFailedException('该课程设计已经存在!');
        }

        $addCourse = Course::create([
            'id'=> null,
            'course_name'=> $request->course_name,
            'belong_class'=> $this->user['class'],
            'introduce'=> $request->introduce,
            'status'=> 0,
            'user_id'=> null,
            'chooser'=> null,
            'is_custom'=> 0,
        ]);
        return $this->response->item($addCourse , new CourseListsTransformer());
    }
    /**
     * 修改本班的课程设计
     */
    public function updateCourse(Request $request , $id)
    {
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
        $course = Course::where(['belong_class'=>$this->user['class'] , 'id'=>$id])->first();
        $course->course_name = $request->course_name;
        $course->introduce = $request->introduce;

        if(!$course->save()){
            throw new StoreResourceFailedException('修改课程设计失败.');
        }
        return $this->response->item($course , new CourseListsTransformer());
    }
    /**
     * 删除课程设计
     */
    public function delCourse($id)
    {
        $course = Course::where(['id'=>$id])->first();
        if(!$course){
            throw new Exception('删除失败,没有找到该课程设计.');
        }
        //如果有人选择了该课程设计,则同时删除该选择者的选择信息.
        if($course->user_id != NULL){
            $userInfo = User::where(['id' =>$course->user_id])->first();
            $userInfo->selected_course = NULL;
            $userInfo->save();
        }
        $course->delete();
        return $this->response->array(['msg'=>'删除成功.']);
    }


    /**
     * 判断该班级是不是已经存在该题目
     */
    private function existedCourse($course_name){

        $lists = Course::where([
            'belong_class'=> $this->user['class'],
            'course_name'=>$course_name,
        ])->get();
        //判断有没有查到数据
        if($lists->isEmpty()){
            return false;
        }
        return true;
    }
}
