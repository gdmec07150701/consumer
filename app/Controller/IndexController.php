<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Controller;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Utils\ApplicationContext;
use Hyperf\View\RenderInterface;
use Hyperf\HttpServer\Contract\RequestInterface;


class IndexController extends AbstractController
{
    public function index(RenderInterface $render)
    {
        return $render->render('index', ['name' => 'Hyperf']);
    }

    public function store(RenderInterface $render, RequestInterface $request){
        ini_set("memory_limit", "1024M");
        if ($request->hasFile('excel')) {
            $file = $request->file('excel');
            // 由于 Swoole 上传文件的 tmp_name 并没有保持文件原名，所以这个方法已重写为获取原文件名的后缀名
            $extension = $file->getExtension();
            if(!in_array($extension, array('xls', 'xlsx', 'csv'))){
                return ['status'=> 100,  'message' => '后缀需要是xls, xlsx, csv'];
            }
            $fileName = $file->getClientFilename();
            $targetPath = BASE_PATH.'/storage/upload/'.$fileName;
            $file->moveTo($targetPath);
            if($file->isMoved()){
                $container = ApplicationContext::getContainer();
                $redis = $container->get(\Redis::class);
                $redis->rpush('waitFile', json_encode(array('fileName' => $fileName, 'frameId' => $request->input('frameId'))));
                return ['status'=> 200,  'message' => $fileName.'已成功保存，请等待处理'];
            }
            return ['status'=> 100,  'message' => $fileName.'上传有错误，请联系'];
        }
        return ['status'=> 100,  'message' => '没收到文件'];

    }

    public function upload(RenderInterface $render){
        return $render->render('index', ['name' => 'Hyperf']);
    }
}
