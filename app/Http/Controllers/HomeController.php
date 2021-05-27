<?php

namespace App\Http\Controllers;

use App\Country;
use App\Payment;
use App\TUser;
use App\User;
use BotMan\Drivers\Telegram\Exceptions\TelegramException;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use SoapClient;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;
use Zarinpal\Laravel\Facade\Zarinpal;
use Log;

class HomeController extends Controller
{
    public function webhook(Request $request)
    {
        Telegram::commandsHandler(true);
        $updates = Telegram::getWebhookUpdates();
        $from = $updates->getMessage()->getChat()->getId();
        sleep(2);
        $User = TUser::where('username', $from)->first() ?? TUser::create([
                'username' => $from
            ]);
        if (str_contains($updates->getMessage()->getText(), 'ارسال مجدد کد')) {
            $this->verify($from, $request, $User,true);
            return response()->json(['status'=>true]);
        }
        if (str_contains($updates->getMessage()->getText(), 'تغیر شماره')) {
            $User->update(['phone' => null, 'verified' => false, 'tcode' => null, 'tcode_expires' => null]);
        }
        // Check Phone Number
        if (!$User->phone) {
            // Get Number From User And Send Code To Him
            $this->getNumber($updates, $from);
        } else {
            if ($User->verified) {
                $this->handler($updates);
            } else {
                // Verify User
                $this->verify($from, $request, $User);
            }
        }
        return response()->json(['success' => true]);
    }

    private function sendSms($input, $from)
    {
        $client = new \SoapClient('http://37.130.202.188/class/sms/wsdlservice/server.php?wsdl');
        $user = "panel62";
        $pass = "1397@m#iR";
        $fromNum = "+98100020400";
        $toNum = array($input);
        $pattern_code = "121";
        $code = strtolower(str_random(5));
        $input_data = array("activate-code" => $code);
        $client->sendPatternSms($fromNum, $toNum, $user, $pass, $pattern_code, $input_data);
        // Manipulate User Data
        TUser::where('username', $from)->update([
            'tcode' => $code,
            'phone' => $input,
            'tcode_expires' => Carbon::now()->addMinutes(5)
        ]);
    }

    private function verify($from, Request $request, TUser $User,$again=false)
    {
        if (is_null($User->tcode)) {
            $this->sendSms($User->phone, $from);
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "کد فعالسازی به تلفن همراه شما ارسال شده \n کد فعالسازی حساب خود را وارد کنید :",
                'reply_markup' => json_encode([
                    'keyboard' => [
                        ['ارسال مجدد کد'],
                        ['تغیر شماره']
                    ],
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
        } elseif ($User->tcode_expires < Carbon::now()) {
            sleep(1);
            if($again){
                Telegram::sendMessage([
                    'chat_id' => $from,
                    'text' => ' تا دقایقی دیگر کد جدیدی برای شما ارسال میشود ' . PHP_EOL . 'کد دریافتی خود را وارد کنید :' ,
                    'reply_markup' => json_encode([
                        'keyboard' => [
                            ['تغیر شماره']
                        ],
                        "resize_keyboard" => true,
                        "one_time_keyboard" => true
                    ])
                ]);
            }else {
                Telegram::sendMessage([
                    'chat_id' => $from,
                    'text' => 'کد شما منقضی شده ، تا دقایقی دیگر کد جدیدی برای شما ارسال میشود ' . PHP_EOL . 'کد دریافتی خود را وارد کنید :' ,
                    'reply_markup' => json_encode([
                        'keyboard' => [
                            ['تغیر شماره']
                        ],
                        "resize_keyboard" => true,
                        "one_time_keyboard" => true
                    ])
                ]);
            }
            $this->sendSms($User->phone, $from);
        } else {
            if ($User->tcode == trim(strtolower($request->input('message.text')))) {
                $User->update(['verified' => true, 'tcode' => null, 'tcode_expires' => null]);
                sleep(1);
                Telegram::sendMessage([
                    'chat_id' => $from,
                    'text' => "حساب شما با موفقیت تایید شد . \n حال میتوانید به راحتی شماره مجازی بخرید",
                    "reply_markup" => json_encode([
                        'keyboard' => [
                            ['تعرفه ها','خرید شماره مجازی'],
                            ['تست ۱','تست ۲']
                        ],
                        "resize_keyboard" => true,
                        "one_time_keyboard" => true

                    ])
                ]);
            } else {
                sleep(1);
                if($again){
                    Telegram::sendMessage([
                        'chat_id' => $from,
                        'text' => "دوباره تلاش کنید یا 5 دقیقه دیگر برای دریافت کد مجدد مراجعه فرمایید .",
                        'reply_markup' => json_encode([
                            'keyboard' => [
                                ['ارسال مجدد کد'],
                                ['تغیر شماره']
                            ],
                            "resize_keyboard" => true,
                            "one_time_keyboard" => true
                        ])
                    ]);
                }else {
                    Telegram::sendMessage([
                        'chat_id' => $from,
                        'text' => "کد وارد شده اشتباه میباشد ! \n دوباره تلاش کنید یا 5 دقیقه دیگر برای دریافت کد مجدد مراجعه فرمایید .",
                        'reply_markup' => json_encode([
                            'keyboard' => [
                                ['ارسال مجدد کد'],
                                ['تغیر شماره']
                            ],
                            "resize_keyboard" => true,
                            "one_time_keyboard" => true
                        ])
                    ]);
                }
            }
        }
    }

    private function getNumber(Update $updates, $from)
    {
        if ($updates->getMessage()->getEntities()[0]['type'] == "phone_number") {
            $this->sendSms($updates->getMessage()->getText(), $from);
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "کد فعالسازی به تلفن همراه شما ارسال شده \n کد فعالسازی حساب خود را وارد کنید :"
            ]);
        } else {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => 'شماره تلفن خود را وارد کنید :'
            ]);
        }
    }

    private function handler($updates)
    {
        $from = $from = $updates->getMessage()->getChat()->getId();
        if (str_contains($updates->getMessage()->getText(), 'خرید شماره مجازی')) {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "شبکه مجازی مورد نظر خود را انتخاب کنید",
                "reply_markup" => json_encode([
                    'keyboard' => [
                        ['واتساپ', 'تلگرام'],
                        ['اینستاگرام', 'گوگل']
                    ],
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true

                ])
            ]);
        } elseif (in_array($updates->getMessage()->getText(), ['تلگرام', 'واتساپ', 'اینستاگرام', 'گوگل'])) {
            switch ($updates->getMessage()->getText()) {
                case "تلگرام":
                    $service = "tg";
                    break;
                case "واتساپ":
                    $service = "wa";
                    break;
                case "اینستاگرام":
                    $service = "ig";
                    break;
                case "گوگل":
                    $service = "go";
                    break;
                default:
                    $service = "tg";
                    break;
            }
            TUser::whereUsername($from)->update(['service' => $service]);
            goto buy;
        } elseif (str_contains($updates->getMessage()->getText(), 'تست ۱')) {
            sleep(1);
            $keyboard = [
                "keyboard" => [
                    [
                        [
                            "text" => "بازگشت به منو اصلی"
                        ]
                    ]
                ],
                "resize_keyboard" => true,
                "one_time_keyboard" => true
            ];
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "تست ۱",
                "reply_markup" => json_encode($keyboard)
            ]);
        }elseif (str_contains($updates->getMessage()->getText(), 'تعرفه ها')) {
            $keyboard = [
                'keyboard' => [
                    ['بازگشت به منو اصلی','خرید شماره مجازی']
                ],
                "resize_keyboard" => true,
                "one_time_keyboard" => true
            ];
            Telegram::sendChatAction([
                'chat_id' => $from,
                'action' => 'typing'
            ]);
            $text = "✅ لیست قیمت ها ".PHP_EOL . PHP_EOL;
            foreach (Country::all() as $country) {
                $text .= "⭕️ " . $country->name . " ⬅️ " . $country->price . " تومان " . PHP_EOL . PHP_EOL;
            }
            Telegram::sendChatAction([
                'chat_id' => $from,
                'action' => 'typing'
            ]);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => $text,
                "reply_markup" => json_encode($keyboard)
            ]);
        }elseif (str_contains($updates->getMessage()->getText(), 'تست ۲')) {
            sleep(1);
            $keyboard = [
                "keyboard" => [
                    [
                        [
                            "text" => "بازگشت به منو اصلی"
                        ]
                    ]
                ],
                "resize_keyboard" => true,
                "one_time_keyboard" => true
            ];
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "تست ۲",
                "reply_markup" => json_encode($keyboard)
            ]);
        }elseif(str_contains($updates->getMessage()->getText(), 'بازگشت به منو اصلی')){
            sleep(1);
            $keyboard = [
                'keyboard' => [
                    ['تعرفه ها','خرید شماره مجازی'],
                    ['تست ۱','تست ۲']
                ],
                "resize_keyboard" => true,
                "one_time_keyboard" => true
            ];
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => 'یکی از منوی های زیر را انتخاب نمایید',
                "reply_markup" => json_encode($keyboard)
            ]);
        }elseif (str_contains($updates->getMessage()->getText(), 'شماره مجازی')) {
            buy:
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "کشور مورد نظر را انتخاب کنید :",
                "reply_markup" => json_encode([
                    "keyboard" => [
                        ["روسیه","قزاقستان","نینجریه"],
                        ["چین", "میانمار","انگلیس"],
                        ["اندونزی", "مالزی","لهستان"],
                        ["آمریکا", "اسرائیل", "هنگ کنگ"],
                        ['بازگشت به منو اصلی']
                    ],
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
        } elseif (str_contains($updates->getMessage()->getText(), 'پرداخت انجام شد')) {
            $User = TUser::whereUsername($from)->first();
            $payment = $User->payments()->latest()->first();
            if (!$payment->status) {
                $res = $this->payVerify($payment->ref);
                if ($res) {
                    $payment->update(['status' => true]);
                    $this->orderNumber($payment, $from, $User);
                }else {
                    $keyboard = [
                        "keyboard" => [
                            [
                                [
                                    "text" => "بازگشت به منو اصلی"
                                ]
                            ]
                        ],
                        "resize_keyboard" => true,
                        "one_time_keyboard" => true
                    ];
                    sleep(1);
                    Telegram::sendMessage([
                        'chat_id' => $from,
                        'text' => "پرداخت شما انجام نشده یا خطایی پیش آمده \n دوباره تلاش کنید یا با پشتیبانی تماس بگیرید ",
                        "reply_markup" => json_encode($keyboard)
                    ]);
                }
            } else {
                $keyboard = [
                    "keyboard" => [
                        [
                            [
                                "text" => "بازگشت به منو اصلی"
                            ]
                        ]
                    ],
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ];
                sleep(1);
                Telegram::sendMessage([
                    'chat_id' => $from,
                    'text' => "پرداخت شما قبلا چک شده و نتیجه خدمتتون ارسال شده !",
                    "reply_markup" => json_encode($keyboard)
                ]);
            }
        } elseif (str_contains($updates->getMessage()->getText(), 'دریافت مجدد کد')) {
            $User = TUser::whereUsername($from)->first();
            Telegram::sendChatAction([
                'chat_id' => $from,
                'action' => 'typing'
            ]);
            $this->sendAgain($from, $User, $updates);
        } elseif (str_contains($updates->getMessage()->getText(), 'کد درست بود')) {
            $User = TUser::whereUsername($from)->first();
            Telegram::sendChatAction([
                'chat_id' => $from,
                'action' => 'typing'
            ]);
            $this->codeWasRight($from, $User, $updates);
            goto buy;
        } elseif (str_contains($updates->getMessage()->getText(), 'دریافت کد')) {
            $User = TUser::where('username', $from)->first();
            if ($User->vphone) {
                $this->getCode($updates, $from, $User);
            } else {
                $keyboard = [
                    "keyboard" => [
                        [
                            [
                                "text" => "بازگشت به منو اصلی"
                            ]
                        ]
                    ],
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ];
                sleep(1);
                Telegram::sendMessage([
                    'chat_id' => $from,
                    'text' => 'شماره ای جهت دریافت کد وجود ندارد !',
                    'reply_markup' => json_encode($keyboard)
                ]);
            }
        } elseif ($Country = Country::whereName($updates->getMessage()->getText())->first()) {
            $User = TUser::where('username', $from)->first();
            Telegram::sendChatAction([
                'chat_id' => $from,
                'action' => 'typing'
            ]);
            $HasNumber = $this->hasNumbers($Country, $User);
            $HasBalance = $this->hasBalance();
            if ($HasBalance && $HasNumber) {
                $res = $this->payReq(intval($Country->price) * 10,$User,route('verify') ,'خرید شماره مجازی ' . $Country->name);
                if (!boolval($res)) {
                    sleep(1);
                    Telegram::sendMessage([
                        'chat_id' => $from,
                        'text' => "مشکلی در پرداخت به وجود آمده . \n لطفا دوباره تلاش کنید . ",
                        "reply_markup" => json_encode([
                            "keyboard" => [
                                ["روسیه","قزاقستان","نینجریه"],
                                ["چین", "میانمار","انگلیس"],
                                ["اندونزی", "مالزی","لهستان"],
                                ["آمریکا", "اسرائیل", "هنگ کنگ"],
                                ['بازگشت به منو اصلی']
                            ],
                            "resize_keyboard" => true,
                            "one_time_keyboard" => true
                        ])
                    ]);
                } else {
                    $link = $res[0];
                    //$link = $this->shortLink($link);
                    $User->payments()->create([
                        'price' => $Country->price,
                        'ref' => $res[1],
                        'country_id' => $Country->id
                    ]);
                    $text = "کاربر گرامی برای شما فاکتوری با جزئیات زیر ایجاد شده \n";
                    $text .= PHP_EOL . "قیمت : " . $Country->price . " تومان ";
                    $text .= PHP_EOL . "وضعیت پرداخت : در انتظار پرداخت";
                    $text .= PHP_EOL . "لینک پرداخت : " . $link;
                    $text .= PHP_EOL . PHP_EOL . "( پس از پرداخت روی دکمه - پرداخت انجام شد - کلیک کنید )";
                    $keyboard = [
                        "keyboard" => [
                            [
                                [
                                    "text" => "پرداخت انجام شد"
                                ],
                                [
                                    "text" => "بازگشت به منو اصلی"
                                ]
                            ]
                        ],
                        "resize_keyboard" => true,
                        "one_time_keyboard" => true
                    ];
                    sleep(2);
                    Telegram::sendMessage([
                        'chat_id' => $from,
                        'text' => $text,
                        "reply_markup" => json_encode($keyboard)
                    ]);
                }
            } else {
                sleep(1);
                $keyboard = [
                    "keyboard" => [
                        [
                            [
                                "text" => "بازگشت به منو اصلی"
                            ]
                        ]
                    ],
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ];
                Telegram::sendMessage([
                    'chat_id' => $from,
                    'text' => 'شماره مجازی های این کشور تمام شده است',
                    "reply_markup" => json_encode($keyboard)
                ]);
            }
        }
    }

    private function orderNumber(Payment $payment, $from, TUser $user)
    {
        Telegram::sendChatAction([
            'chat_id' => $from,
            'action' => 'typing'
        ]);
        $client = new Client();
        $res = $this->post('http://sms-activate.ru/stubs/handler_api.php',[
            'api_key' => env('VIRTUAL_SITE_KEY', 'c451b639316edc6665A7A7d050875bfd'),
            'action' => 'getNumber',
            'service' => $user->service,
            'country' => Country::where('id', $payment->country_id)->first()->slug
        ]);
        if ($res == "NO_KEY") {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                "text" => "مشکلی در سیستم هست لطفا به پشتیبان پیام دهید .",
                'reply_markup' => json_encode([
                    'keyboard' => [
                        [
                            'بازگشت به منو اصلی'
                        ]
                    ],
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
            return response()->json(["status" => "success"]);
        } elseif (str_contains($res, 'ACCESS_NUMBER')) {
            $arr = explode(':', $res);
            $user->update(['vphone' => $arr[2], 'vphone_id' => $arr[1]]);
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "سفارش شما با موفقیت انجام شد ! \n شماره شما : {$arr[2]} \n آیدی مربوط به شماره : {$arr[1]} \n (جهت ارائه به پشتیبانی)",
            ]);
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => 'حالا شماره را در پیامرسان خود وارد کنید و پس از 20 ثانیه روی دکمه دریافت کد کلیک کنید ' . PHP_EOL . "(پس از اطمینان کلیک کنید امکان از بین رفتن سفارش شما دارد)",
                'reply_markup' => json_encode([
                    'keyboard' => [
                        [
                            'دریافت کد'
                        ]
                    ]
                    ,
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
        } elseif ($res == "NO_NUMBERS") {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "خطایی در ارتباط به وجود امده \n لطفا با پشتیبانی تماس بگیرید",
                'reply_markup' => json_encode([
                    'keyboard' => [
                        ['بازگشت به منو اصلی']
                    ]
                    ,
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => env('ADMIN_TG_USER_ID', '130926814'),
                'text' => "شماره مجازی در سیستم سایت شماره مجازی وجود ندارد \n همچنین از کاربری وجه دریافت شده ولی نتوانستیم به او شماره مجازی بدیم ."
            ]);
        } elseif ($res = "NO_BALANCE") {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "خطایی در ارتباط به وجود امده \n لطفا با پشتیبانی تماس بگیرید",
                'reply_markup' => json_encode([
                    'keyboard' => [
                        ['بازگشت به منو اصلی']
                    ]
                    ,
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => env('ADMIN_TG_USER_ID', '130926814'),
                'text' => 'موجودی شما در وبسایت شماره مجازی به پایان رسیده و کاربری خرید انجام داده ولی نتوانستیم شماره مجازی به او ارسال کنیم .'
            ]);
        } elseif (str_contains($res, "BANNED")) {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "خطایی در ارتباط به وجود امده \n لطفا با پشتیبانی تماس بگیرید",
                'reply_markup' => json_encode([
                    'keyboard' => [
                        ['بازگشت به منو اصلی']
                    ]
                    ,
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => env('ADMIN_TG_USER_ID', '130926814'),
                'text' => 'حساب شما در وبسایت شماره مجازی بلاک شده و کاربری خرید انجام داده ولی نتوانستیم به او شماره مجازی ارسال کنیم.'
            ]);
        } else {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "خطایی در ارتباط به وجود امده \n لطفا با پشتیبانی تماس بگیرید",
                'reply_markup' => json_encode([
                    'keyboard' => [
                        ['بازگشت به منو اصلی']
                    ]
                    ,
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => env('ADMIN_TG_USER_ID', '130926814'),
                'text' => "خطایی ناشناخته در سیستم به وجود امده و کد این خطا" . " $res " . "میباشد و کاربری خرید انجام داده و نتوانستیم شماره مجازی را به آن ارسال کنیم ."
            ]);
        }
    }

    private function hasNumbers(Country $country, TUser $user)
    {
        $res = $this->post('http://sms-activate.ru/stubs/handler_api.php',[
            'api_key' => env('VIRTUAL_SITE_KEY', "956ecb9742826Af32881328b7133cf08"),
            'action' => "getNumbersStatus",
            'country' => $country->slug
        ]);
        $res = json_decode($res, true);
        if (intval($res[$user->service . "_0"]) < 10) {
            return false;
        }
        return true;
    }

    private function hasBalance()
    {
        $res = $this->post('http://sms-activate.ru/stubs/handler_api.php',[
            'api_key' => env('VIRTUAL_SITE_KEY', "956ecb9742826Af32881328b7133cf08"),
            'action' => "getBalance"
        ]);
        $res = explode(':', $res);
        if (intval($res[1]) < env('MIN_BALANCE', 2)) {
            return false;
        }
        return true;
    }

    private function getCode(Update $update, $from, TUser $user)
    {
        Telegram::sendChatAction([
            'chat_id' => $from,
            'action' => 'typing'
        ]);
        $res = $this->post('http://sms-activate.ru/stubs/handler_api.php',[
            'api_key' => env('VIRTUAL_SITE_KEY', '956ecb9742826Af32881328b7133cf08'),
            'action' => 'getStatus',
            'id' => $user->vphone_id
        ]);
        if (str_contains($res, 'STATUS_WAIT_CODE')) {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => 'هنوز کدی دریافت نشده کمی دیگر مراجعه کنید !'
            ]);
        } elseif (str_contains($res, 'STATUS_WAIT_RESEND')) {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => 'در انتظار ارسال کد مجدد ، کمی دیگر مراجعه کنید !'
            ]);
        } elseif (str_contains($res, 'STATUS_CANCEL')) {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => 'سفارش شما غیر فعال شده ، لطفا با پشتیبانی تماس بگیرید !',
                'reply_markup' => json_encode([
                    'keyboard' => [
                        ['بازگشت به منو اصلی']
                    ]
                    ,
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
            $user->update(['vphone' => null, 'vphone_id' => null]);
        } elseif (str_contains($res, 'STATUS_OK')) {
            $code = explode(':', $res);
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "کد دریافت شد !‌ \n کد : " . $code[1],
                'reply_markup' => json_encode([
                    'keyboard' => [
                        [
                            'کد درست بود', 'دریافت مجدد کد'
                        ]
                    ],
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
        } elseif (str_contains($res, 'STATUS_WAIT_RETRY')) {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "در انتظار تایید از سوی شما !",
                'reply_markup' => json_encode([
                    'keyboard' => [
                        [
                            'کد درست بود', 'دریافت مجدد کد'
                        ]
                    ],
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
        }
    }

    private function sendAgain($from, TUser $user, Update $update)
    {
        $res = $this->post('http://sms-activate.ru/stubs/handler_api.php',[
            'api_key' => env('VIRTUAL_SITE_KEY'),
            'action' => 'setStatus',
            'id' => $user->vphone_id,
            'status' => '3'
        ]);

        if (str_contains($res, 'ACCESS_RETRY_GET')) {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "در انتظار برای دریافت کد جدید ... ! "
            ]);
        } else {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "خطایی در سیستم به وجود آمده \n دوباره تلاش کنید یا به پشتیبان اطلاع دهید."
            ]);
        }
    }

    private function codeWasRight($from, TUser $user, Update $update)
    {
        $res = $this->post('http://sms-activate.ru/stubs/handler_api.php',[
            'api_key' => env('VIRTUAL_SITE_KEY'),
            'action' => 'setStatus',
            'id' => $user->vphone_id,
            'status' => '6'
        ]);
        if (str_contains($res, 'ACCESS_ACTIVATION')) {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "تشکر از استفاده از سرویس های ما . ",
                'reply_markup' => json_encode([
                    'keyboard' => [
                        ['بازگشت به منو اصلی']
                    ]
                    ,
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
            $this->sendLog($user->payments()->latest()->first(), $from, $user);
            $user->update(['vphone' => null, 'vphone_id' => null]);
        } else {
            sleep(1);
            Telegram::sendMessage([
                'chat_id' => $from,
                'text' => "خطایی در سیستم به وجود آمده \n دوباره تلاش کنید یا به پشتیبان اطلاع دهید.",
                'reply_markup' => json_encode([
                    'keyboard' => [
                        ['بازگشت به منو اصلی']
                    ]
                    ,
                    "resize_keyboard" => true,
                    "one_time_keyboard" => true
                ])
            ]);
        }
    }

    private function sendLog(Payment $payment, TUser $user)
    {
        $Country = Country::find($payment->country_id);
        $text = "📱یک عدد شماره مجازی «کشور" . $Country->name . "» خریداری شد! \n
                ⚜️اطلاعات شماره و خریدار 👇 \n
                ➖➖➖➖➖➖➖➖ \n 
                number : + " . substr($user->vphone, 0, -4) . "**** \n
                ➖➖➖➖➖➖➖➖ \n 
                user : " . substr($user->username, 0, -4) . "**** \n
                ➖➖➖➖➖➖➖➖
                ❗️روش خرید و دریافت شماره مجازی :  \n
                ۱-وارد ربات @xbot شوید.\n  
                ۲-کشور " . $Country->name . " را انتخاب کنید \n
                ۳- مبلغ " . $Country->price . "تومان پرداخت کنید\n  
                ۴- شماره را تحویل بگیرید \n 
                ☝️شماره های مجازی فروخته شده، اختصاصی هستند، یعنی ثبت نشده و با متد های اتومات توسط کاربران ربات " . config('telegram.bots.mybot.username') . " به صورت کاملا خودکار فقط برای یک کاربر، دریافت و ثبت می شوند. \n
                این پیام خودکار با دریافت کد شماره مجازی توسط کاربر ربات " . config('telegram.bots.mybot.username') . " ارسال شده است. \n
                ******************* \n
                🤖 " . config('telegram.bots.mybot.username') . " \n
                🖥 http://mydomin.com \n
                🔊" . env('Channel_Send_Log');
        sleep(1);
        Telegram::sendMessage([
            'chat_id' => env('Channel_Send_Log'),
            'text' => $text
        ]);
    }

    private function shortLink($link){
        $json = json_decode(file_get_contents('https://api-ssl.bitly.com/v3/shorten?access_token=eeb4553d4cea6ac092bfcb72f55ddf7a72783177&longUrl='.$link));
        if($json->status_code == '200'){
            return $json->data->url;
        }else {
            return $link;
        }
    }


    public function post($url,$data)
    {
        $client = new Client();
        $response = $client->request('POST', $url, [
            'form_params' => $data
        ])->getBody()->getContents();
        return $response;
        /*$options = [
            CURLOPT_URL        => $url,
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $data,
        ];
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($curl);
        curl_close($curl);
        Log::info((array)$res);
        return $res;*/

        /*$postdata = http_build_query($data);
        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context  = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        return $result;*/
    }

    private function payReq($price,TUser $user,$callbackURL,$desc)
    {
        $Parameters = array(
            'SandBox'			  => false,
            'MerchantCode'  	  => env('PAY_MERCH','fpapi-9299'),
            'PriceValue'   		  => intval($price),
            'ReturnUrl'    		  => $callbackURL,
            'InvoiceNumber'		  => time(),
            'CustomQuery'   	  => [],
            'CustomPost'          => [],
            'PaymenterName'       => $user->phone,
            'PaymenterEmail' 	  => 'info@instaking.com',
            'PaymenterMobile' 	  => $user->phone,
            'PluginName' 		  => 'Laravel',
            'PaymentNote'		  => $desc,
            'ExtraAccountNumbers' => [],
            'Bank'				  => '',
        );

        $client  = new SoapClient('https://farapal.com/services/soap?wsdl', array('encoding' => 'UTF-8') );
        $Request = $client->PaymentRequest( $Parameters );
        if ( isset($Request->Status) && $Request->Status == 1 ){
            $Token = isset($Request->Token) ? $Request->Token : '';
            $Payment_URL = route('pay',['ref'=>$Token]);
            return [$Payment_URL,$Token];
        }
        else {
            return false;
        }
    }

    public function toGateway($ref=null){
        if(is_null($ref)){
            abort(404);
        }
        return redirect('https://farapal.com/services/payment/'.$ref)->send();
    }

    private function payVerify($ref){

        $client = new SoapClient('https://farapal.com/services/soap?wsdl', array('encoding' => 'UTF-8') );
        $Request = $client->PaymentVerify( array(
                'SandBox' 	   => false,
                'MerchantCode' => env('PAY_MERCH','fpapi-9299'),
                'Token' 	   => $ref
            )
        );
        if( isset($Request->Status) && $Request->Status == 1 ){
            return true;
        }
        else {
            return false;
        }
    }

    public function verifyPayment()
    {
        return redirect('https://t.me/'.config('telegram.bots.mybot.username'))->send();
    }

}
