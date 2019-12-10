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

use function foo\func;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Utils\ApplicationContext;
use Hyperf\View\RenderInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use MathPHP\Exception\BadDataException;
use Swoole\Exception;
use Hyperf\Guzzle\ClientFactory;
use GuzzleHttp\Client;
use Hyperf\Guzzle\CoroutineHandler;
use GuzzleHttp\HandlerStack;

/**
 * @AutoController()
 */
class IndexController extends AbstractController
{
    public $length = 0;

    public function index(RenderInterface $render)
    {
        return $render->render('index', ['name' => 'Hyperf']);
    }

    public function store(RenderInterface $render, RequestInterface $request)
    {
        ini_set("memory_limit", "1024M");
        if ($request->hasFile('excel')) {
            $file = $request->file('excel');
            // 由于 Swoole 上传文件的 tmp_name 并没有保持文件原名，所以这个方法已重写为获取原文件名的后缀名
            $extension = $file->getExtension();
            if (!in_array($extension, array('xls', 'xlsx', 'csv'))) {
                return ['status' => 100, 'message' => '后缀需要是xls, xlsx, csv'];
            }
            $fileName = $file->getClientFilename();
            $targetPath = BASE_PATH . '/storage/upload/' . $fileName;
            $file->moveTo($targetPath);
            if ($file->isMoved()) {
                $container = ApplicationContext::getContainer();
                $redis = $container->get(\Redis::class);
                $redis->rpush('waitFile', json_encode(array('fileName' => $fileName, 'frameId' => $request->input('frameId'))));
                return ['status' => 200, 'message' => $fileName . '已成功保存，请等待处理'];
            }
            return ['status' => 100, 'message' => $fileName . '上传有错误，请联系'];
        }
        return ['status' => 100, 'message' => '没收到文件'];

    }

    public function getRandNum()
    {
        return array('num' => mt_rand(), 'createdTime' => time());
    }

    public function testCurl()
    {
        co(function () {
            $client = new Client([
                'base_uri' => 'http://op.juhe.cn/robot/index',
                'handler' => HandlerStack::create(new CoroutineHandler()),
                'timeout' => 5,
                'swoole' => [
                    'timeout' => 10,
                    'socket_buffer_size' => 1024 * 1024 * 2,
                ],
            ]);
            $channel = new \Swoole\Coroutine\Channel(10000);
            for ($i = 0; $i < 100000; $i++) {
                co(function () use ($channel, $i, $client) {
                    $arr = $this->coPostCurl('http://47.99.237.196:9503/index/getRandNum', array());
                    while (!is_array($arr)){
                        $arr = $this->coPostCurl('http://47.99.237.196:9503/index/getRandNum', array());
                    }
                    echo $i;
                    $channel->push($arr);
                });
            }

            for($i = 0; $i < 100000; $i++){
                $dbData[] = $channel->pop();
            }
            Db::table('hy_test2')->insert($dbData);
//            $k = 0;
//            $dbData = array();
//            var_dump($channel->isEmpty());
//            while (!$channel->isEmpty()) {
//                var_dump(1);
//                $k++;
//                $dbData[] = $channel->pop();
//            }
//            var_dump($dbData);
//
        });
        return '完成';
    }

    //普通curl_post
    public static function curlPost($url, $post_data = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        if (!empty($post_data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * swoole协程客户端
     */
    public function coPostCurl($url, $data)
    {
        $apiParseInfo = parse_url($url);
        $ssl = isset($apiParseInfo['scheme']) && strpos($apiParseInfo['scheme'], 'https') !== false;
        if (empty($apiParseInfo['port'])) {
            $apiParseInfo['port'] = $ssl ? 443 : 80;
        }
        $path = preg_replace('/[^\/]*(\/\/)?' . $apiParseInfo['host'] . '(:\d+)?\//isU', '/', $url);
        $client = new \Swoole\Coroutine\Http\Client($apiParseInfo['host'], $apiParseInfo['port'], $ssl);
        $client->setHeaders(["Content-Type" => "application/json", "Host" => $apiParseInfo['host']]);
        $client->post($path, json_encode($data));
        $response = json_decode($client->getBody(), true);
        $client->close();
        return $response;
    }

    public function upload(RenderInterface $render)
    {
        return $render->render('index', ['name' => 'Hyperf']);
    }
}
