# RubikaBot
---

## معرفی

کلاس `Bot` برای ساخت و مدیریت ربات در روبیکا با امکانات زیر طراحی شده است:

- ارسال پیام، فایل، موقعیت مکانی، مخاطب، نظرسنجی
- ویرایش و حذف پیام‌ها
- فوروارد پیام
- مدیریت کیبورد سفارشی و اینلاین
- دریافت اطلاعات گفتگو و کاربر
- دریافت آپدیت‌ها و پردازش رویدادها

---

## ساخت نمونه Bot

```php
use RubikaBot\Bot;

$bot = new Bot('توکن-ربات');
```

می‌توانید پارامتر دوم را برای تنظیمات مانند timeout و max_retries ارسال کنید.

---

## ارسال پیام

```php
$bot->chat($chat_id)
    ->message('متن پیام')
    ->send();
```

- `chat($chat_id)` : تعیین آی‌دی چت مقصد
- `message($text)` : تعیین متن پیام
- `send()` : ارسال پیام متنی

### ارسال پیام پاسخ به یک پیام

```php
$bot->chat($chat_id)
    ->message('پاسخ')
    ->replyTo($message_id)
    ->send();
```

---

## ارسال فایل

```php
$bot->chat($chat_id)
    ->file('/path/to/file.jpg')
    ->caption('توضیح فایل')
    ->sendFile();
```

- `file($path)` : مسیر فایل روی سرور
- `caption($caption)` : توضیح فایل (اختیاری)
- `sendFile()` : ارسال فایل

### ارسال فایل با file_id (در صورتی که قبلاً آپلود شده)

```php
$bot->chat($chat_id)
    ->file_id('file_id')
    ->file_type('Image')
    ->sendFile();
```

---

## ارسال مکان

```php
$bot->chat($chat_id)
    ->location($lat, $lng)
    ->sendLocation();
```

---

## ارسال مخاطب

```php
$bot->chat($chat_id)
    ->contact('نام مخاطب', 'شماره تلفن')
    ->sendContact();
```

---

## ارسال نظرسنجی

```php
$bot->chat($chat_id)
    ->poll('سوال؟', ['گزینه ۱', 'گزینه ۲', 'گزینه ۳'])
    ->sendPoll();
```

---

## فوروارد پیام

```php
$bot->forwardFrom($from_chat_id)
    ->messageId($message_id)
    ->forwardTo($to_chat_id)
    ->forward();
```

---

## ویرایش پیام متنی

```php
$bot->chat($chat_id)
    ->messageId($message_id)
    ->message('متن جدید')
    ->sendEditText();
```

---

## حذف پیام

```php
$bot->chat($chat_id)
    ->messageId($message_id)
    ->sendDelete();
```

---

## کار با کیبورد

### ساخت کیبورد سفارشی

```php
use RubikaBot\Keypad;
use RubikaBot\Button;

$keypad = Keypad::make();
$keypad->row()
        ->add(Button::simple('btn1', 'دکمه ۱'))
        ->add(Button::simple('btn2', 'دکمه ۲'))
    );

$bot->chat($chat_id)
    ->message('انتخاب کنید:')
    ->chatKeypad($keypad->toArray())
    ->send();
```

### کیبورد اینلاین

```php
$bot->chat($chat_id)
    ->message('انتخاب کنید:')
    ->inlineKeypad($keypad->toArray())
    ->send();
```

---

## دریافت اطلاعات چت و کاربر

```php
$bot->getChat(['chat_id' => $chat_id]);
$type = $bot->getChatType(); // نوع چت (User, Group, Channel, Bot)
$name = $bot->getFirstName();
$username = $bot->getUserName();
```

---

## دریافت اطلاعات پیام دریافتی

- `getUpdate()` : دریافت آپدیت فعلی
- `getChatId()` : آی‌دی چت پیام دریافتی
- `getMessageId()` : آی‌دی پیام دریافتی
- `getSenderId()` : آی‌دی ارسال کننده
- `getText()` : متن پیام دریافتی
- `getFileId()` : آی‌دی فایل پیام دریافتی
- ...

---

## مدیریت رویدادها (هندلرها)

برای پاسخ به پیام خاص:

```php
$bot->onMessage(
    $bot->filterText('/start'),
    function(Bot $bot) {
        $bot->chat($bot->getChatId())
            ->message('سلام! خوش آمدید')
            ->send();
    }
);
$bot->run();
```

---

## دریافت اطلاعات ربات

```php
$bot->getMe();
```

---

## نکات مهم

- تمام متدها به صورت chainable هستند و می‌توانند پشت سر هم فراخوانی شوند.
- در صورت نبود پارامترهای ضروری، خطای InvalidArgumentException صادر می‌شود.
- برای استفاده از کیبوردها و دکمه‌ها، کلاس‌های Button و Keypad به کار می‌روند.
- برای فیلتر کردن پیام‌ها از متدهای filter استفاده کنید و هندلرها را با onMessage ثبت نمایید.

---

## Enumها

```php
enum ChatType: string {
    case USER = 'User';
    case GROUP = 'Group';
    case CHANNEL = 'Channel';
    case BOT = 'Bot';
}
```

---

## مثال کامل بوت ساده

```php
$bot = new Bot('TOKEN');

$bot->onMessage(
    $bot->filterCommand('start'),
    function(Bot $bot) {
        $bot->chat($bot->getChatId())
            ->message('سلام!')
            ->send();
    }
);

$bot->run();
```

---

## کلاس‌های کاربردی (Button, Keypad و ...)

برای ساخت کیبورد و دکمه‌های مختلف از کلاس‌های Button و Keypad استفاده کنید. به مثال‌های بالا مراجعه شود.

---

## جمع‌بندی

این کلاس امکانات اصلی برای ساخت انواع ربات روبیکا را فراهم کرده است و با متدهای chainable توسعه را بسیار ساده می‌کند. برای توسعه حرفه‌ای‌تر می‌توانید قابلیت‌های خود را با استفاده از همین متدهای اصلی گسترش دهید.
