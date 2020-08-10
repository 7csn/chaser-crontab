<?php

namespace chaser\crontab;

use chaser\container\Container;
use chaser\container\ContainerException;
use Closure;

class Task
{
    /**
     * 行程
     *
     * @var Closure|string
     */
    protected $action;

    /**
     * 时间表
     *
     * @var array
     */
    protected $timetable = [];

    /**
     * 上次行动时间
     *
     * @var array
     */
    protected $actioned = [];

    /**
     * 是否处于行动中
     *
     * @var bool
     */
    protected $actioning = false;

    /**
     * 初始化时间表
     *
     * @param string $times
     * @param Closure|string $action
     * @throws FormatException
     */
    public function __construct(string $times, $action)
    {
        $this->action = $action;
        $this->timetable = self::timetable($times);
    }

    /**
     * 获取时间指令规则
     *
     * @param string $times
     * @return array
     * @throws FormatException
     */
    public static function timetable(string $times)
    {
        $parts = explode(' ', $times);

        if (!isset($parts[4]) || isset($parts[5])) {
            throw new FormatException("$times 不是正确的时间指令格式");
        }

        $minutes = self::timeSet($parts[0]);
        if (empty($minutes)) {
            throw new FormatException("时间指令[$times]解析分时日月周，[分]为空");
        }

        $hours = self::timeSet($parts[1]);
        if (empty($hours)) {
            throw new FormatException("时间指令[$times]解析分时日月周，[时]为空");
        }

        $days = self::timeSet($parts[2], 1, 31);
        if (empty($days)) {
            throw new FormatException("时间指令[$times]解析分时日月周，[日]为空");
        }

        $months = self::timeSet($parts[3], 1, 12);
        if (empty($months)) {
            throw new FormatException("时间指令[$times]解析分时日月周，[月]为空");
        }

        $weeks = self::timeSet($parts[4], 0, 6);
        if (empty($weeks)) {
            throw new FormatException("时间指令[$times]解析分时日月周，[周]为空");
        }

        return [$minutes, $hours, $days, $months, $weeks];
    }

    /**
     * 解析有效时间集合
     *
     * @param string $part
     * @param int $min
     * @param int $max
     * @param array $set
     * @param bool $single
     * @return array
     * @throws FormatException
     */
    public static function timeSet(string $part, int $min = 0, int $max = 59, array $set = [], bool $single = false)
    {
        if ($single || strpos($part, ',') === false) {
            if (preg_match('/^\*(\/([1-d]\d*))?$/', $part, $match)) {
                $start = $min;
                $end = $max;
                $step = empty($match[2]) ? 1 : $match[2];
            } elseif (preg_match('/^(0|[1-9]\d*)(-([1-9]\d*))?(\/([1-9]\d*))?$/', $part, $match)) {
                $start = max($match[1], $min);
                $end = min(empty($match[3]) ? $start : $match[3], $max);
                $step = empty($match[5]) ? 1 : $match[5];
            } else {
                throw new FormatException("格式错误：$part 不符合 min[-max][/step]");
            }
            for ($i = $start; $i <= $end; $i += $step) {
                $set[$i] = 1;
            }
        } else {
            $set = array_reduce(explode(',', $part), function ($set, $part) use ($min, $max) {
                return self::timeSet($part, $min, $max, $set, true);
            }, $set);
        }
        return $set;
    }

    /**
     * 转化任务时间格式
     *
     * @param int $time
     * @return array
     */
    public static function format(int $time)
    {
        return explode('-', date('i-G-j-n-w', $time));
    }

    /**
     * 闹钟走时（处于时间表则执行任务）
     *
     * @param Container $container
     * @throws ContainerException
     */
    public function tick(Container $container)
    {
        $format = self::format(time());
        if ($this->inTimetable($format)) {
            $this->action($format, $container);
        }
    }

    /**
     * 执行任务
     *
     * @param array $format
     * @param Container $container
     * @throws ContainerException
     */
    protected function action(array $format, Container $container)
    {
        $this->actioning = true;
        $container->call($this->action);
        $this->actioned = $format;
        $this->actioning = false;
    }

    /**
     * 验证：指定格式时间是否处于时间表
     *
     * @param array $format
     * @return bool
     */
    protected function inTimetable(array $format)
    {
        if ($this->actioning || $this->actioned === $format) {
            return false;
        }

        foreach ($this->timetable as $index => $rule) {
            if (!isset($format[$index]) || !isset($rule[$format[$index]])) {
                return false;
            }
        }

        return true;
    }
}
