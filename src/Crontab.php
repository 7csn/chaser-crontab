<?php

namespace chaser\crontab;

use chaser\container\Container;

/**
 * 定时任务
 *
 * @package chaser\crontab
 */
class Crontab
{
    /**
     * 任务列表
     *
     * @var Task[]
     */
    protected $tasks = [];

    /**
     * 查看日程间隔
     *
     * @var int
     */
    protected $interval;

    /**
     * 初始化任务列表
     *
     * @param array $schedules
     * @param int $interval
     * @throws FormatException
     */
    public function __construct(array $schedules, int $interval = 1)
    {
        $this->interval = $interval;
        array_walk($schedules, function ($action, $time) {
            $this->tasks[] = new Task($time, $action);
        });
    }

    /**
     * 定时任务轮询
     *
     * @param Container $container
     */
    public function loop(Container $container)
    {
        while (1) {
            array_walk($this->tasks, function (Task $task) use ($container) {
                $task->tick($container);
            });
            sleep($this->interval);
        }
    }
}
