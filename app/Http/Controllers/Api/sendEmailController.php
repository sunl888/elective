<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;

class sendEmailController extends Controller
{
    public function send(){
        $data = [
            ['email'=>'1972324624@qq.com','name'=>'2'],
            ['email'=>'1252286502@qq.com','name'=>'3'],
            ['email'=>'1757457637@qq.com','name'=>'3'],
        ];
        foreach($data as $value){
            Mail::send('activemail', $value, function($message) use($value)
            {
                $message->to($value['email'], $value['name'])->subject('from Laravel');
            });
        }
    }
}
