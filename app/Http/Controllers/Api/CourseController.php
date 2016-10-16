<?php
/**
 * Created by PhpStorm.
 * User: 孙龙
 * Date: 2016/10/11
 * Time: 18:45
 */
namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Course;
use App\Transformers\UserTransformer;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Transformers\CourseListsTransformer;
use Dingo\Api\Exception\StoreResourceFailedException;

class CourseController extends BaseApiController
{
    protected $validationRoles = [
        'course_name' => 'required|min:3',
        'introduce'=>'required|min:3',
    ];

    /**
     * 返回个人信息
     */
    public function me()
    {
        $user = JWTAuth::user();

        $course = Course::where(['user_id'=>$user->id])->first();
        if(is_null($course)){
            $user['introduce'] = '';
            $user['costom'] = 0;
            $user['course_id'] = 0;
        }else{
            $user['course_id'] = $course->id;//可通过此id来修改课程设计内容
            $user['costom'] = $course->costom;//表示该题目是不是自己添加的 默认:0
            $user['introduce'] = $course->introduce;//题目的详情
        }
        return $this->response->item($user , new UserTransformer());
    }
    /**
     * 根据已登陆用户的班级列出该班所有的课程设计题目
     */
    public function courseLists()
    {
        $user = JWTAuth::user();
        $lists = Course::where('belong_class' , $user->class)->get();
        return $this->response->collection($lists , new CourseListsTransformer());
    }

    /**
     * 选择课程设计
     *
     */
    public function selectCourse($id)
    {
        $user = JWTAuth::user();
        //判断该用户以前有没有选过课程设计
        if($this->isChoosed() != null){
            throw new StoreResourceFailedException('你已经选过课程设计.');
        }
        //查找用户选择的题目信息
        $course = Course::where(['id'=>$id , 'status'=>0])->first();
        if($course == null){
            throw new StoreResourceFailedException('该题目已经被其他同学选择.');
        }
        $course->status = 1;
        $course->chooser = $user->name;
        $course->user_id = $user->id;

        $uInfo = User::find($user->id);
        $uInfo->selected_course = $course->course_name;
        //保存更改
        if(!$course->save() || !$uInfo->save()){
            throw new StoreResourceFailedException('选择课程设计失败.');
        }
        return $this->response->noContent();//选择成功
    }

    /**
     * [班长]添加课程设计
     */
    public function addCourse(Request $request){
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
        $user = JWTAuth::user();
        //判断添加的课程设计是否存在
        if($this->existedCourse($request->course_name)){
            throw new \Dingo\Api\Exception\StoreResourceFailedException('该课程设计已经存在!');
        }
        $addCourse = Course::create([
            'id'=>null,
            'course_name'=> $request->course_name,
            'belong_class'=> $user->class,
            'introduce'=>$request->introduce,
            'status'=>0,
            'user_id'=>null,
            'chooser'=>null,
            'custom'=>0,
        ]);
        return $this->response->item($addCourse , new CourseListsTransformer());
    }

    /**
     * 判断用户有没有选择课程设计
     */
    private function isChoosed(){
        $user = JWTAuth::user();
        return $user->selected_course;
    }
    /**
     * 判断该班级是不是已经存在该题目
     */
    private function existedCourse($course_name){
        $user = JWTAuth::user();
        $lists = Course::where([
            'belong_class'=> $user->class,
            'course_name'=>$course_name,
        ])->get();
        //判断有没有查到数据
        if($lists->isEmpty()){
            return false;
        }
        return true;
    }

    /**
     * 返回课程设计详情
     */
    public function courseDetail($id){
        return $this->response()->item(Course::find($id) , new CourseListsTransformer());
    }
}
