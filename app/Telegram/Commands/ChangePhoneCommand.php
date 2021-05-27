<?php
namespace App\Telegram\Commands;


use App\TUser;
use Telegram\Bot\Commands\Command;

class ChangePhoneCommand extends Command
{
    protected $name = 'تغیر شماره';

    /**
     * @var string The Telegram command description.
     */
    protected $description = 'شماره تلفن خود را عوض کنید';
    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        TUser::update(['phone'=>null,'verified' => false]);
        $this->triggerCommand('start');
    }
}