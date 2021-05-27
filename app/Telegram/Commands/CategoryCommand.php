<?php

namespace App\Telegram\Commands;


use App\Country;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class CategoryCommand extends Command
{
    protected $name = 'buy';

    protected $description = 'خرید شماره مجازی';
    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $from = $this->getUpdate()->getMessage()->from->id;
        $keyboard = [
            "keyboard" => [
                [
                    [
                        "text" => "button"
                    ]
                    ,[
                    "text" => "button"
                    ]
                ]
            ]
        ];
        Telegram::sendMessage([
            'chat_id' => $from,
            'text' => "کشور مورد نظر را انتخاب کنید :",
            "reply_markup" => json_encode($keyboard)
        ]);
    }
}