<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Utils\ApplicationContext;
use Hyperf\DbConnection\Db;
use PHPExcel;
use PHPExcel_IOFactory;

class AddRedisProcess extends AbstractProcess
{
    /**
     * 进程数量
     * @var int
     */
    public $nums = 1;

    /**
     * 进程名称
     * @var string
     */
    public $name = 'add-redis-process';

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
        $waitFile = $redis->lPop('waitFile');
        if($waitFile){
            $fileName = '/www/wwwroot/f2m/storage/upload/2019-11-25/'.$waitFile;
            $data = $this->excelToArray($fileName);
            $allData = array_chunk($data,999);
            $listKey = $waitFile.date('YmdHis');
            var_dump($listKey);
            foreach ($allData as $k1 => $v1){
                $redis->rpush($listKey, serialize($v1));
            }
            $redis->rpush('canConsumer', $listKey);
        }
    }

    public function excelToArray($fileName){
        $excel = PHPExcel_IOFactory::load($fileName);
        $curSheet = $excel->getActiveSheet();
        $rows = $curSheet->getHighestRow();
        $cols = $curSheet->getHighestColumn();
        $data = array();
        $firstCol = array();

        for($i = 1; $i <= $rows; ++$i) {
            $tmp = array();
            for($j = 'A'; $j <= $cols; ++$j) {
                $name = $j . $i;
                $cellData = $curSheet->getCell($name)->getValue();
                $tmp[] = $cellData;
            }
            if($i == 1){
                $firstCol = $tmp;
            }else{
                foreach ($tmp as $k => $v){
                    $tmp[$firstCol[$k]] = $v;
                    unset($tmp[$k]);
                }
                $data[] = $tmp;
            }
        }
        return $data;
    }
}