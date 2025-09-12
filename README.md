# مستندات جامع RubikaBot.php

این فایل یک کتابخانه کامل برای ساخت ربات در روبیکا است. بخش‌های مختلف آن عبارتند از: مدیریت پیام‌ها، ارسال انواع فایل و موقعیت، ساخت دکمه‌های پیشرفته، کنترل اسپم کاربران و تعریف هندلرهای دلخواه برای انواع پیام‌ها.

---

## ۱. Enumها

### ChatType
```php
enum ChatType: string {
    case USER = 'User';
    case GROUP = 'Group';
    case CHANNEL = 'Channel';
    case BOT = 'Bot';
}
```
- مقادیر مجاز برای نوع چت (کاربر، گروه، کانال، ربات).

---

## ۲. کلاس اصلی Bot

### هدف:
مدیریت ربات و همه عملیات‌های مربوط به ارسال و دریافت پیام، فایل و کنترل اسپم.

### ویژگی‌ها:
- token: توکن ربات.
- hashedToken: توکن هش شده جهت ذخیره داده‌های اسپم.
- baseUrl: آدرس API ربات روبیکا.
- config: تنظیمات مثل timeout، تعداد retry.
- update, chat: داده‌های آخرین پیام و چت.
- handlers: لیست هندلرهای پیام.
- updateTypes: انواع آپدیت قابل دریافت.
- مقادیر builder_*: برای ساخت پیام و فایل‌ها.
- مقادیر اسپم: مدیریت کاربران اسپمر.

### مهم‌ترین متدها Bot:
- **__construct($token, $config = [])**: مقداردهی اولیه ربات و خواندن داده‌های اسپم.
- **chat($chat_id)**: انتخاب چت مقصد پیام.
- **message($text)**: انتخاب متن پیام.
- **replyTo($message_id)**: پاسخ به پیام خاص.
- **file($path)**: انتخاب فایل برای ارسال.
- **file_id($file_id), file_type($type)**: ارسال فایل با شناسه و نوع.
- **caption($caption)**: افزودن توضیح به فایل.
- **poll($question, $options)**: ساخت نظرسنجی.
- **location($lat, $lng)**: ارسال موقعیت جغرافیایی.
- **contact($first_name, $phone_number)**: ارسال مخاطب.
- **inlineKeypad($keypad), chatKeypad($keypad, $type)**: افزودن دکمه به پیام.
- **forwardFrom($id), forwardTo($id), messageId($id)**: فوروارد پیام.
- **send()**: ارسال پیام متنی.
- **sendFile()**: ارسال فایل.
- **sendPoll()**: ارسال نظرسنجی.
- **sendLocation()**: ارسال لوکیشن.
- **sendContact()**: ارسال مخاطب.
- **forward()**: فوروارد پیام.
- **editMessage()**: ویرایش پیام.
- **sendDelete()**: حذف پیام.
- **getMe()**: دریافت اطلاعات ربات.
- **getChat($data), getUpdates($data)**: دریافت اطلاعات چت و آپدیت‌ها.
- **setCommands($data)**: ثبت دستورات ربات.
- **updateBotEndpoints($url, $type), setEndpoint($url)**: تنظیم URL endpoint.
- **isUserSpamming($userId), isUserSpamDetected($userId), resetUserSpamState($userId)**: مدیریت اسپم کاربران.
- **getUserMessageCount($userId), cleanupSpamData($expireTime)**: دریافت تعداد پیام و پاکسازی داده‌های اسپم.
- **getFile($file_id), downloadFile($file_id, $to)**: دریافت و دانلود فایل.
- **onMessage($filter, $callback)**: تعریف هندلر برای پیام با فیلتر دلخواه.
- **run()**: اجرای ربات و هندلرها.
- **getLastResponse()**: دریافت آخرین پاسخ API.

---

## ۳. کلاس Bot مخصوص **onMessage**

### هدف:
دریافت داده پیام جاری و متدهای پاسخ سریع به همان پیام.

### ویژگی‌ها و دریافت انواع محتوا:
- update_type: نوع آپدیت پیام.
- chat_id: شناسه چت پیام.
- sender_id: شناسه ارسال‌کننده پیام.
- text: متن پیام.
- button_id: شناسه دکمه کلیک شده.
- file_name, file_id, file_size: اطلاعات فایل پیام.
- message_id: شناسه پیام.
- chat_type, first_name, user_name: اطلاعات چت.

### متدهای کاربردی :
- **reply()**: پاسخ متنی به پیام جاری.
- **replyFile()**: پاسخ فایل به پیام جاری.
- **replyContact()**: پاسخ مخاطب به پیام جاری.
- **replyLocation()**: پاسخ لوکیشن به پیام جاری.
- **editText()**: ویرایش پیام جاری.
- **delete()**: حذف پیام جاری.

---

## ۴. کلاس Filter و Filters

### Filter
ایجاد شرط برای هندلر پیام؛ می‌تواند چند شرط با and/or ترکیب کند.

- **make($condition)**: ساخت فیلتر جدید.
- **and($condition), or($condition)**: ترکیب شرط‌ها.
- **__invoke($bot)**: اجرا روی پیام.
- **markAsSpamHandler()**: علامت‌گذاری فیلتر برای هندلر اسپم.
- **isSpamHandler()**: بررسی نوع فیلتر اسپم.

### Filters
انواع فیلترهای آماده:
- **filter($condition)**: ساخت فیلتر از شرط.
- **text($match)**: فیلتر روی متن خاص.
- **command($command)**: فیلتر دستور.
- **button($buttonId)**: فیلتر دکمه.
- **chatId($chat_id)**: فیلتر چت.
- **senderId($sender_id)**: فیلتر ارسال‌کننده.
- **ChatTypes($chatType)**: فیلتر نوع چت.
- **spam($maxMessages, $timeWindow, $cooldown)**: فیلتر اسپم.

---

## ۵. کلاس Button

برای ساخت انواع دکمه‌های روبیکا:
- **simple($id, $text)**: دکمه ساده.
- **selection($id, $title, $items, $multi, $columns)**: دکمه انتخابی.
- **calendar($id, $title, $calendarType, $min, $max)**: دکمه تقویم.
- **numberPicker($id, $title, $min, $max, $default)**: دکمه عددی.
- **stringPicker($id, $title, $items, $default)**: دکمه انتخاب رشته.
- **location($id, $title, $type)**: دکمه لوکیشن.
- **payment($id, $title)**، **cameraImage**، **cameraVideo**، **galleryImage** و غیره.
- **textBox($id, $title, $lineType, $keypadType)**: دکمه با تکست‌باکس.
- **link($id, $title, $type, $link)**: دکمه لینک.

---

## ۶. Keypad و KeypadRow

برای ساخت آرایه‌ای از دکمه‌ها و ارسال به کاربر:
- **KeypadRow::add($button)**: افزودن دکمه به ردیف.
- **Keypad::row()->add(Button)**: افزودن ردیف.
- **Keypad::setResize($bool), setOnetime($bool)**: تنظیم اندازه و حالت کیپد.
- **toArray()**: تبدیل به آرایه جهت ارسال.

---

## ۷. ButtonLinkType، JoinChannelData، OpenChatData، ButtonLink

برای ساخت دکمه با لینک، پیوستن به کانال یا چت:
- **ButtonLinkType::URL, JoinChannel**
- **JoinChannelData::make($username, $ask_join)**
- **OpenChatData::make($chat_id)**
- **ButtonLink::make($link_url, $type, $joinchannel_data, $open_chat_data)**

---

## مثال عملی استفاده از همه امکانات

```php
<?php
require 'RubikaBot.php';
use RubikaBot\Bot;
use RubikaBot\Filters;
use RubikaBot\Button;
use RubikaBot\Keypad;
use RubikaBot\Message;
use RubikaBot\ChatType;

// تعریف ربات و توکن
$bot = new Bot('TOKEN_BOT');

// هندلر دستور شروع و ارسال کیپد
$bot->onMessage(Filters::command('start'), function(Message $message){
    $btn1 = Button::simple('btn1', 'ثبت نام');
    $btn2 = Button::calendar('btn2', 'انتخاب تاریخ', 'Persian');
    $keypad = Keypad::make();
    $keypad->row()->add($btn1)->add($btn2));
    $message->message('یکی از گزینه‌ها را انتخاب کنید.')
        ->inlineKeypad($keypad->toArray())
        ->chatKeypad($keypad->toArray())
        ->reply();
});

// هندلر روی دکمه ثبت نام
$bot->onMessage(Filters::button('btn1') // Inline Keypad, function(Bot $message){
    $message->message('لطفا نام خود را وارد نمایید:')->reply();
});

// هندلر روی دکمه تقویم
$bot->onMessage(Filters::button('btn2') //inline Keypad, function(Bot $message){
    $message->message('تاریخ انتخاب شد!')->reply();
});
$bot->onMessage(Filters::text('ثبت نام') //chatKeypad, function(Bot $message){
    $message->message('لطفا نام خود را وارد نمایید:')->reply();
});

// هندلر روی دکمه تقویم
$bot->onMessage(Filters::text('انتخاب تاریخ') //chatKeypad, function(Bot $message){
    $message->message('تاریخ انتخاب شد!')->reply();
});
// ارسال فایل به کاربر
$bot->onMessage(Filters::command('file'), function(Bot $message){
    $message->file('test.jpg')->caption('این یک تصویر است')->replyFile();
});

// ارسال لوکیشن به کاربر
$bot->onMessage(Filters::command('location'), function(Bot $message){
    $message->location(35.6892, 51.3890)->replyLocation();
});

// ارسال مخاطب به کاربر
$bot->onMessage(Filters::command('contact'), function(Bot $message){
    $message->contact('علی', '09120000000')->replyContact();
});

// هندلر اسپم
$bot->onMessage(Filters::spam(5, 10, 120), function(Bot $message){
    $message->message('پیام زیاد فرستادی!')->reply();
});

// هندلر فقط برای گروه‌ها
$bot->onMessage(Filters::ChatTypes(ChatType::GROUP), function(Bot $message){
    $message->message('این پیام فقط برای گروه است.')->reply();
});

// اجرای ربات
$bot->run();
```

---

## نکات و توصیه‌ها

- **onMessage**: همیشه فیلتر و تابع را با هم بدهید، تابع دوم باید Bot دریافت کند
- **send، sendFile، sendLocation و ...**: قبل از آن chat_id را ست کنید.
- **Button و Keypad**: برای ساخت دکمه و کیپد حرفه‌ای استفاده کنید.
- **فیلتر اسپم**: بلافاصله پیام اسپم را برای کاربر ارسال کنید.
