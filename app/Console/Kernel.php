<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Collection\Dongguan\CompanyListCommand::class,
        \App\Console\Commands\Collection\Dongguan\CompanyCertPersonCommand::class,
        \App\Console\Commands\Collection\Dongguan\CompanyCertCommand::class,
        \App\Console\Commands\Collection\Dongguan\CompanyPersonCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //采集企业信息列表
        $schedule->command('company_list_command')
            ->dailyAt('00:00')
            ->sendOutputTo('storage/logs/collect/company_list_command.log', true);

        //采集企业资质和人才信息
        $schedule->command('company_cert_person_command')
            ->dailyAt('00:20')
            ->sendOutputTo('storage/logs/collect/company_cert_person_command.log', true);

        //采集企业资质推送给job执行
        $schedule->command('company_cert_command')
            ->dailyAt('02:25')
            ->sendOutputTo('storage/logs/collect/company_cert_command.log', true);

        //采集企业人才证书推送给job执行
        $schedule->command('company_person_command')
            ->dailyAt('02:25')
            ->sendOutputTo('storage/logs/collect/company_person_command.log', true);
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
