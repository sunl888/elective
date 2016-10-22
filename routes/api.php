<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|使用put请求的时候 could not get response.不知道为什么
*/

//$api->get('sendEmail', 'sendEmailController@send');

//登录系统 1
$api->post('login', 'AuthenticateController@authenticate');

$api->group(['middleware' => 'jwt.auth'], function ($api) {

    //1.显示该班级所有的课程设计题目
    $api->get('course_lists', 'CourseController@courseLists');

    //2.返回个人信息(包括选择的课程信息)
    $api->get('me', 'CourseController@me');

    //3.通过id返回课程设计详情
    $api->get('course_detail/{id}', 'CourseController@courseDetail');
    //通过id选择题目
    $api->get('select_course/{id}', 'CourseController@selectCourse');

    //取消选择
    //$api->post('cancel_course/{id}', 'CourseController@cancelCourse');

    //自定义课程设计 input: course_name , introduce
    $api->post('add_custom','CustomCourseController@addCustomCourse');

    //修改自定义的课程设计 input: course_id , course_name , introduce
    $api->post('up_course/{id}', 'CustomCourseController@upCourse');

    //[班长]添加课程设计
    $api->post('add_course', 'CourseController@addCourse');

});