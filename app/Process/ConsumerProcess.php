<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Utils\ApplicationContext;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Frame;

class ConsumerProcess extends AbstractProcess
{

    /**
     * @Inject
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * 进程数量
     * @var int
     */
    public $nums = 2;

    /**
     * 进程名称
     * @var string
     */
    public $name = 'consumer-process';

    /**
     * 重定向自定义进程的标准输入和输出
     * @var bool
     */
    public $redirectStdinStdout = false;

    /**
     * 管道类型
     * @var int
     */
    public $pipeType = 2;

    /**
     * 是否启用协程
     * @var bool
     */
    public $enableCoroutine = true;

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        $redis = $container->get(\Redis::class);
        $isContinue = true;
        while ($isContinue) {
            $count = $redis->llen('canConsumer');
            if ($count > 0) {
                $canConsumers = $redis->lRange('canConsumer', 0 , 10);
                foreach ($canConsumers as $k => $val){
                    $canItem = unserialize($val);
                    $insertData = $redis->lPop($canItem['listKey']);
                    if($insertData){
                        echo '开始插入数据库';
                        $res = Db::table(substr($canItem['listKey'], 0 , strrpos($canItem['listKey'],".")))->insert(unserialize($insertData));
                        if(!$redis->lLen($canItem['listKey']) && empty($redis->get('notice:'.$canItem['listKey']))){
                            $redis->lRem('canConsumer',$val,0);
                            $host = '127.0.0.1:9502';
                            $client = $this->clientFactory->create($host);
                            $msg = substr($canItem['listKey'], 0 , strrpos($canItem['listKey'],".")).'表成功创建';
                            $client->push(json_encode(array('from' => 'server', 'toFrameId' => $canItem['frameId'], 'msg' => $msg)));
                            $redis->set('notice:'.$canItem['listKey'], '1');
                            $redis->expire('notice:'.$canItem['listKey'], 1000);
                        }
                    }

                }
            }else{
                $isContinue = false;
            }
        }
    }
}
