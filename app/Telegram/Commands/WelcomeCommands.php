<?php

namespace App\Telegram\Commands;

use App\TUser;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Laravel\Facades\Telegram;
use GuzzleHttp\Client;
/**
 * Class HelpCommand.
 */
class WelcomeCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'start';

    /**
     * @var string Command Description
     */
    protected $description = 'شروع به کار';

    /**
     * {@inheritdoc}
     */

    public function handle()
    {
        $from = $this->update->getMessage()->getChat()->getId();
        // Check User
        $User = TUser::where('username', $from)->first();
        $firstTime = false;
        if (!$User) {
            $User = TUser::create([
                'username' => $from,
            ]);
            $firstTime = true;
        }
        $User->update([
            'tcode' => null,
            'tcode_expires' => null,
        ]);
        Telegram::sendChatAction([
            'chat_id' => $from,
            'action' => 'typing'
        ]);
        if ($firstTime) {
            $text = 'به ربات شماره مجازی خوش آمدید';
            Telegram::sendChatAction([
                'chat_id' => $from,
                'action' => 'typing'
            ]);
            $this->replyWithMessage([
                'text' => $text . PHP_EOL . PHP_EOL ."عضویت اجباری در کانال ها به دلیل غیر اخلاقی بودن این کار و جلوگیری از نارضایتی کاربران حذف شده است \n\n ولی برای استفاده بهتر از ربات و مطلع شد از اخبار ربات ، توصیه میکنیم در کانال های ما عضو شوید \n \n " . PHP_EOL . "🆔 " . env('Channel_JOIN') . PHP_EOL . "🆔 " . env('Channel_JOIN2')
            ]);
        } else {
            if ($User->phone) {
                $text = 'به ربات شماره مجازی خوش آمدید';
                $this->replyWithMessage([
                    'text' => $text . PHP_EOL . PHP_EOL . "عضویت اجباری در کانال ها به دلیل غیر اخلاقی بودن این کار و جلوگیری از نارضایتی کاربران حذف شده است \n\n ولی برای استفاده بهتر از ربات و مطلع شد از اخبار ربات ، توصیه میکنیم در کانال های ما عضو شوید \n \n " . PHP_EOL . "🆔 " . env('Channel_JOIN') . PHP_EOL . "🆔 " .  env('Channel_JOIN2'),
                    "reply_markup" => json_encode([
                        'keyboard' => [
                            ['تعرفه ها','خرید شماره مجازی'],
                            ['تست ۱', 'تست ۲'],
                        ],
                        "resize_keyboard" => true,
                        "one_time_keyboard" => true,

                    ]),
                ]);
            } else {
                $text = 'به ربات شماره مجازی خوش آمدید';
                $this->replyWithMessage([
                    'text' => $text . PHP_EOL . PHP_EOL . "عضویت اجباری در کانال ها به دلیل غیر اخلاقی بودن این کار و جلوگیری از نارضایتی کاربران حذف شده است \n\n ولی برای استفاده بهتر از ربات و مطلع شد از اخبار ربات ، توصیه میکنیم در کانال های ما عضو شوید \n \n " . PHP_EOL . "🆔 " . env('Channel_JOIN') . PHP_EOL . "🆔 " .  env('Channel_JOIN2'),
                ]);
            }
        }
    }
}
