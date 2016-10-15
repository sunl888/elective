<?php
/**
 * Created by PhpStorm.
 * User: 孙龙
 * Date: 2016/10/11
 * Time: 16:26
 */
namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Ty666\Login2hnnuJwc\Facades\Login2hnnuJwc;
use Ty666\Login2hnnuJwc\Exception\LoginJWCException;

class AuthenticateController extends BaseApiController
{
    /*
     * 验证规则
     * 学号,身份证
     */
    protected $validationRules=[
        'number' => ['required' , 'regex:/^\d{10}$/'],
        'password' => ['required' , 'regex:/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/'],
    ];
    protected $guard = 'user';

    public function authenticate(Request $request)
    {
        /**
         * 数据验证
         */
        $validator = Validator::make($request->input() , $this->validationRules);
        if($validator->fails()){
            throw new \Dingo\Api\Exception\StoreResourceFailedException('登陆失败!', $validator->errors());
        }
        $input = $request->only('number' , 'password');

        if (!($token = JWTAuth::attempt($input))) {
            try {
                Login2hnnuJwc::login2Jwc($input['number'], $input['password']);
                $userInfo = Login2hnnuJwc::getStudentInfoFromJWC();
                //把用户信息添加到users表
                $userInfo = User::create([
                    'number'=> $input['number'],
                    'password'=> $input['password'],
                    'name'=> $userInfo['student_name'],
                    'class'=> $userInfo['student_class'],
                ]);
                $token = JWTAuth::fromUser($userInfo);
            } catch (LoginJWCException $e) {
                dd($e->getMessage());
            }
        }
        return ['token' => $token];
    }
}
