<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Utils\ApplicationContext;
use Hyperf\DbConnection\Db;
use PHPExcel;
use Hyperf\Database\Schema\Schema;
use Hyperf\Di\Annotation\Inject;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Frame;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;


class AddRedisProcess extends AbstractProcess
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
        $container = ApplicationContext::getContainer();
        $redis = $container->get(\Redis::class);
        $waitFile = $redis->lPop('waitFile');
        if($waitFile){
            $waitFile = json_decode($waitFile, true);
            $fileName = BASE_PATH.'/storage/upload/'.$waitFile['fileName'];
            var_dump('开始转array'.date('H:i:s'));
            list($data,$col, $rowLen) = $this->excelToArray($fileName);
            var_dump('结束转'.date('H:i:s'));
            $tableName = substr($waitFile['fileName'], 0 , strrpos($waitFile['fileName'],"."));
            $res = $this->createTable($tableName, $col, $waitFile);
            if($res){
                $allData = array_chunk($data,2000);
                $listKey = $waitFile['fileName'].date('YmdHis');
                var_dump('插入到redis:'.$listKey);
                $beginTime = time();
                foreach ($allData as $k1 => $v1){
//                    go(function () use ($redis, $listKey, $v1){
                        echo '插入中...';
                        $redis->rpush($listKey, serialize($v1));
//                    });
                }
               $redis->rpush('canConsumer', serialize(array('listKey' => $listKey, 'frameId' => $waitFile['frameId'])));
                var_dump('完成了转换耗时:'.time()-$beginTime.'秒');

//                $isContinue = true;
//                while ($isContinue){
//                    if($redis->lLen($listKey) >= $rowLen / 2000){
//                        var_dump('完成了转换耗时:'.time()-$beginTime.'秒|'.$redis->lLen($listKey));
//                        $isContinue = false;
//                    }
//                }
            }
        }
    }

    public function createTable($tableName, $col, $waitFile){
        $host = '127.0.0.1:9502';
        $client = $this->clientFactory->create($host);
        try{
            Schema::create($tableName, function ($table) use ($col) {
                $table->increments('id');
                foreach ($col as $k => $v){
                    $table->string($v)->default('')->nullable();
                }
            });
            $client->push(json_encode(array('from' => 'server', 'toFrameId' => $waitFile['frameId'], 'msg' => date('H:i:s',time()).$tableName.'建表成功')));
            return true;
        }catch (\Exception $e){

            $client->push(json_encode(array('from' => 'server', 'toFrameId' => $waitFile['frameId'], 'msg' => $tableName.'已存在')));
            return false;
        }
    }


    public function excelToArray($fileName){
        /** @var Xlsx $objRead */
        $objRead = IOFactory::createReader('Xlsx');

        if (!$objRead->canRead($fileName)) {
            /** @var Xls $objRead */
            $objRead = IOFactory::createReader('Xls');

            if (!$objRead->canRead($fileName)) {
                throw new \Exception('只支持导入Excel文件！');
            }
        }
        $objRead->setReadDataOnly(true);
        $excel = $objRead->load($fileName);
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
        return array($data, $firstCol, $rows);
    }
}