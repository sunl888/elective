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
|
*/

//$api->get('sendEmail', 'sendEmailController@send');


$api->post('login', 'AuthenticateController@authenticate');

$api->group(['middleware' => 'jwt.auth'], function ($api) {

    //显示该班级所有的课程设计题目
    $api->get('course_lists', 'CourseController@courseLists');

    //返回个人信息
    $api->get('me', 'CourseController@me');

    //通过id返回课程设计详情
    $api->put('course_detail/{id}', 'CourseController@courseDetail');

    //通过id选择题目
    $api->put('select_course/{id}', 'CourseController@selectCourse');

    //[班长]添加课程设计
    $api->post('add_course', 'CourseController@addCourse');
    //学生自定义课程设计
    $api->post('add_custom', 'CustomCourseController@addCustomCourse');

});
