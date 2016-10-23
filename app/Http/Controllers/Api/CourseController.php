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
     * 返回用户的信息以及他选择的课程信息
     */
    public function me()
    {
        $user = JWTAuth::user();

        $course = Course::where(['user_id'=>$user->id])->first();

        if(is_null($course)){
            $user['introduce'] = '';
            $user['is_custom'] = 0;
            $user['course_id'] = 0;
        }else{
            $user['course_id'] = $course->id;//可通过此id来修改课程设计内容
            $user['is_custom'] = $course->is_custom;//表示该题目是不是自己添加的 默认:0
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
        //查找用户选择的题目信息
        $course = Course::where(['id'=>$id ,'belong_class'=>$user->class, 'status'=>0])->first();
        if($course == null){
            //该题目不是本班的或者改题目被其他人选择.
            throw new StoreResourceFailedException('该题目已经被其他同学选择,或者你已经选择了该课程设计');
        }
        //判断该用户以前有没有选过课程设计,如果以前选择了则先将以前选择的题目删除
        if($this->isChoosed() != null){
            //throw new StoreResourceFailedException('你已经选过课程设计.');
            $this->cancelCourse($id);
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
        return $this->response()->array(['msg'=>'你已成功选择该课程设计']);//选择成功
    }
    /**
     * 取消选择
     */
    private function cancelCourse($id){
        $user = JWTAuth::user();
        /*if($this->isChoosed() == null){
            throw new StoreResourceFailedException('你还没有选课程设计呢.');
        }*/
        //$course = Course::where(['id' => $id , 'user_id' => $user->id])->first();
        $course = Course::where(['user_id' => $user->id])->first();

        if($course == null){
            throw new StoreResourceFailedException('数据库里没有该课程或者你选择的不是该课程.');
        }
        //判断是不是用户自定义的题目,如果是则取消选择相当于删除该题目
        if($course->is_custom){
            $course->delete();
        }else{
            $course->chooser = null;
            $course->status = 0;
            $course->user_id =null;
            if(!$course->save()){
                throw new StoreResourceFailedException('系统异常.');
            }
        }
        $uInfo = User::find($user->id);
        $uInfo->selected_course = null;
        if(!$uInfo->save()){
            throw new StoreResourceFailedException('系统异常.');
        }
        //return $this->response()->array(['msg'=>'你已成功取消选择该课程设计']);
        return true;
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