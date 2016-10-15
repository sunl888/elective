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

//登录 http://api.elective.com/index.php/login
$api->post('login', 'AuthenticateController@authenticate');

$api->group(['middleware' => 'jwt.auth'], function ($api) {

    //显示该班级所有的课程设计题目 http://api.elective.com/index.php/courseLists?token=
    $api->post('courseLists', 'CourseController@courseLists');

    //通过id选择题目 http://api.elective.com/index.php/selectCourse/id/1?token=
    $api->get('selectCourse/id/{id}', 'CourseController@selectCourse');

    //添加课程设计
    $api->post('addCourse', 'CourseController@addCourse');

});
