<?php

/**
 *要使用响应构建器控制器需要使用Dingo\Api\Routing\Helpers trait.
 * 为了让每个控制器都可以使用这个trait，我们将其放置在API基类控制器Controller中.
 */

namespace App\Http\Controllers\Api;

use Dingo\Api\Routing\Helpers;
use Illuminate\Routing\Controller;


class BaseApiController extends Controller
{
    use Helpers;
}
