<?php
/**
 * Created by PhpStorm.
 * User: Chayton
 * Date: 2017/1/11
 * Time: 17:01
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 使用Command可以不必为每个定时任务都写一个cron，只需要写一个就可以了：* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
 *
 * Class BaseCommand
 * @package App\Console\Commands
 */
class BaseCommand extends Command {

    /**
     * 设置是否在输出信息的前面加上当前的日期和时间，格式为：【2015-11-11 00:00:00】
     * @var bool 默认会在每个输出信息前加上日期和时间，可在子类中改写这个属性
     */
    protected $date_stamp = true;
    private $date_stamp_formatter = '';

    public function __construct() {
        parent::__construct();
        if($this->date_stamp) {
            $this->date_stamp_formatter = date('【Y-m-d H:i:s】');
        }
    }

    /**
     * Write a string as information output.
     *
     * @param  string  $string
     * @return void
     */
    public function info($string)
    {
        $this->output->writeln("<info>$this->date_stamp_formatter $string</info>");
    }

    /**
     * Write a string as standard output.
     *
     * @param  string  $string
     * @return void
     */
    public function line($string)
    {
        $this->output->writeln("$this->date_stamp_formatter $string");
    }

    /**
     * Write a string as comment output.
     *
     * @param  string  $string
     * @return void
     */
    public function comment($string)
    {
        $this->output->writeln("<comment>$this->date_stamp_formatter $string</comment>");
    }

    /**
     * Write a string as question output.
     *
     * @param  string  $string
     * @return void
     */
    public function question($string)
    {
        $this->output->writeln("<question>$this->date_stamp_formatter $string</question>");
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @return void
     */
    public function error($string)
    {
        $this->output->writeln("<error>$this->date_stamp_formatter $string</error>");
    }

    /**
     * Write a string as warning output.
     *
     * @param  string  $string
     * @return void
     */
    public function warn($string)
    {
        $style = new OutputFormatterStyle('yellow');

        $this->output->getFormatter()->setStyle('warning', $style);

        $this->output->writeln("<warning>$this->date_stamp_formatter $string</warning>");
    }

}