<?php
/**
 * desc:这是定时任务规范：
 * 1.一个任务一个进程（一个文件完成一个任务）
 * 2.定时任务文件里一般不写业务逻辑，它只是触发业务逻辑，调用servic里的业务逻辑
 * 3.为了后期定时任务多，统一管理，定时任务统一放到job目录下，定时任务文件统一命名：xxxJob.php
 * 4.一些耗时的业务逻辑，都建议放到计划任务里
 */


/**
 * desc:
 * @author
 */
class QueueJob extends Job {//文件名，都是规定以Job结尾的
    protected $needLocked = true;  //这个很重要（你的任务处理的业务是允许多个进程同时处理：TRUE 不允许；false 允许）

    public function notified() {//函数名是规定的
        header("Content-Type:text/html;charset=utf-8");
        $queueList = [];
        $message = '自动审核以下标：';

        $service = service('Admin/Borrow');
        //维护的标类型 30 90 180 360 天
        $listbase = M('borrow_info bq')
            ->field('bq.id,bi.id AS iid,bq.borrow_duration')
            ->where(['bq.borrow_status' => '-2', 'bq.borrow_duration' => ['in', ['7', '30', '90', '180', '360']], 'bq.reward_rate' => '0', '_string' => 'bi.id IS NULL AND sort.min_sort = bq.sort'])
            ->join("tc_borrow_info bi ON bq.borrow_duration = bi.borrow_duration AND bi.borrow_status = 2 AND bi.reward_rate = 0 AND bi.rookie = bq.rookie")
            ->join("LEFT JOIN (SELECT MIN(sort) AS min_sort,borrow_duration,rookie FROM tc_borrow_info WHERE reward_rate = 0 AND borrow_status = '-2' GROUP BY borrow_duration,rookie) AS sort ON bq.rookie = sort.rookie AND bq.borrow_duration = sort.borrow_duration")
            ->group('bq.borrow_duration,bq.rookie')
            ->select();
        echo M()->getLastSql();
        dump($listbase);
        $listreward = M('borrow_info bq')
            ->field('bq.id,bi.id AS iid,bq.borrow_duration')
            ->where(['bq.borrow_status' => '-2', 'bq.borrow_duration' => ['in', ['7', '30', '90', '180', '360']], 'bq.reward_rate' => ['gt', '0'], '_string' => 'bi.id IS NULL AND sort.min_sort = bq.sort'])
            ->join("tc_borrow_info bi ON bq.borrow_duration = bi.borrow_duration AND bi.borrow_status = 2 AND bi.reward_rate > 0 AND bi.rookie = bq.rookie")
            ->join("LEFT JOIN (SELECT MIN(sort) AS min_sort,borrow_duration,rookie FROM tc_borrow_info WHERE reward_rate > 0 AND borrow_status = '-2' GROUP BY borrow_duration,rookie) AS sort ON bq.rookie = sort.rookie AND bq.borrow_duration = sort.borrow_duration")
            ->group('bq.borrow_duration,bq.rookie')
            ->order('bq.sort')
            ->select();
        echo M()->getLastSql();
        dump($listreward);
        $listbase = (empty($listbase)) ? [] : $listbase;
        $listreward = (empty($listreward)) ? [] : $listreward;
        $list = array_merge($listbase, $listreward);
        dump($list);
        foreach ($list AS $one) {
            dump($one);
            if (empty($one['id'])) {
                echo '不存在' . $one['borrow_duration'] . '天的队列标';
            } else if (!empty($one['iid'])) {
                echo $one['borrow_duration'] . '天的标,任然有可用标，不自动审核';
            } else {
                //尝试初审
                try {
                    $ret = $service->queue($one['id'], 'release');
                } catch (Exception $e) {
                    echo iconv('utf-8', 'gbk', $e->getMessage());
                    $exception['exception_area'] = 'queue';
                    $exception['description'] = $e->getMessage();
                    D("Admin/Exception")->addLog($exception);
                }
                if (true !== $ret) {
                    throw_exception($ret);
                    break;
                }
            }
        }
        if (!empty($queueList)) {
            foreach ($queueList AS $key => $one) {
                $message = $message . $key . '天标，标号：' . $one . ',';
            }
            $exception['exception_area'] = 'queue';
            $exception['description'] = $message . ",通过时间" . time();
            D("Admin/Exception")->addLog($exception);
        }
    }
}
