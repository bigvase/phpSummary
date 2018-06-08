<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/6/1
 * Time: 17:47
 */
namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Test extends Command
{
    protected function configure()
    {
        $this->setName('test')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("TestCommand:");
    }

}


?>