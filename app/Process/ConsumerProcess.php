<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Utils\ApplicationContext;
use Hyperf\DbConnection\Db;
use PHPExcel;
use PHPExcel_IOFactory;

class ConsumerProcess extends AbstractProcess
{
    /**
     * 进程数量
     * @var int
     */
    public $nums = 10;

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
        while (true) {
            $count = $redis->llen('canConsumer');
            if ($count > 0) {
                $val = $redis->lPop('canConsumer');
                if($val){
                    $insertData = $redis->lPop($val);
                    if($insertData){
                        Db::table(substr($val, 0 , strrpos($val,".")))->insert(unserialize($insertData));
                        $redis->rpush('canConsumer', $val);
                    }
                }else{
                    echo '进程跑空咯';
                }
            }
        }
    }
}