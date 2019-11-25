<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Utils\ApplicationContext;
use Hyperf\DbConnection\Db;


class ConsumerProcess extends AbstractProcess
{
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
        // 您的代码 ...

        $container = ApplicationContext::getContainer();

        $redis = $container->get(\Redis::class);
        while (true) {
            $count = $redis->llen('wait');

            if ($count > 0) {
                $val = $redis->lPop('wait');
                if($val){
                    Db::table('wait')->insert(unserialize($val));
                }
            }
        }
    }
}