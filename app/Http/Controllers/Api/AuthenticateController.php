<?php
/**
 * Created by PhpStorm.
 * User: 孙龙
 * Date: 2016/10/11
 * Time: 16:26
 */
namespace App\Http\Controllers\Api;

use App\Models\User;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use Symfony\Component\Routing\Exception\InvalidParameterException;
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
        'password' => ['required' , 'regex:/^\d{17}[\d|X|x]$/'],
    ];
    protected $guard = 'user';

    public function authenticate(Request $request)
    {
        /**
         * 数据验证
         */
        $validator = Validator::make($request->input() , $this->validationRules , [
            'required'=>':attribute 必须要填写.',
            'regex'=>'学号为10位,身份证为18位',
        ],[
            'number'=> '学号',
            'password'=> '身份证',
        ]);
        if($validator->fails()){
            throw new InvalidParameterException($validator->errors()->first());
        }
        $input = $request->all();
        $login = User::where(['number'=>$input['number'],'password'=>$input['password']])->first();
        if(!$login) {
            //本地数据库不存在
            if ( !($token = JWTAuth::attempt($input)) ) {
                try {
                    Login2hnnuJwc::login2Jwc($input['number'], $input['password']);
                    $userInfo = Login2hnnuJwc::getStudentInfoFromJWC();
                    $userInfo = User::create([
                        'number' => $input['number'],
                        'password' => $input['password'],
                        'name' => $userInfo['student_name'],
                        'class' => $userInfo['student_class'],
                    ]);
                    $token = JWTAuth::fromUser($userInfo);
                } catch (LoginJWCException $e) {
                    throw new StoreResourceFailedException($e->getMessage());
                }
            }
        }else{
            $token = JWTAuth::fromUser($login);
        }
        return ['token' => $token];
    }
}