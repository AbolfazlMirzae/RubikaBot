# RubikaBot PHP Library

کتابخانه RubikaBot یک فریم‌ورک ساده و قدرتمند برای ساخت بات روی پلتفرم Rubika با PHP است. این کتابخانه امکانات ارسال پیام، فایل، نظرسنجی، مکان، مخاطب و مدیریت انواع کلیدهای بات را فراهم می‌کند.  
در ادامه راهنمای استفاده و معرفی کلاس‌ها و متدهای مهم آورده شده است.

---

## کلاس اصلی: `Bot`

### سازنده
```php
public function __construct(string $token, array $config = [])
```
ایجاد نمونه بات با توکن اختصاصی و تنظیمات دلخواه:
- `timeout`: زمان تایم‌اوت درخواست‌ها
- `max_retries`: تعداد تلاش مجدد
- `parse_mode`: فرمت متن (Markdown)

---

### ارسال پیام
```php
$bot->chat($chat_id)->message("متن پیام")->send();
```
- `chat($chat_id)`: تعیین شناسه چت
- `message($text)`: تعیین متن پیام
- `send()`: ارسال پیام

---

### ارسال فایل
```php
$bot->chat($chat_id)->file($file_path)->caption("توضیح فایل")->sendFile();
```
- `file($path)`: مسیر فایل
- `caption($caption)`: توضیح فایل (اختیاری)
- `sendFile()`: ارسال فایل

---

### ارسال نظرسنجی
```php
$bot->chat($chat_id)->poll("سوال", ["گزینه1","گزینه2"])->sendPoll();
```
- `poll($question, $options)`: سوال و گزینه‌ها
- `sendPoll()`: ارسال نظرسنجی

---

### ارسال موقعیت مکانی
```php
$bot->chat($chat_id)->location($lat, $lng)->sendLocation();
```
- `location($lat, $lng)`: موقعیت جغرافیایی
- `sendLocation()`: ارسال موقعیت

---

### ارسال مخاطب
```php
$bot->chat($chat_id)->contact("نام", "شماره")->sendContact();
```
- `contact($firstName, $phoneNumber)`: مخاطب
- `sendContact()`: ارسال مخاطب

---

### ارسال و مدیریت کلیدها
- کلیدهای چت (`chatKeypad`) و کلیدهای خطی (`inlineKeypad`)  
نمونه ساخت کیپد:
```php
$keypad = Keypad::make()
    ->addRow((new KeypadRow())->add(Button::simple("id1", "دکمه 1")))
    ->addRow((new KeypadRow())->add(Button::simple("id2", "دکمه 2")));
$bot->chat($chat_id)->chatKeypad($keypad->toArray())->send();
```

---

### فیلتر و هندل کردن پیام‌ها
برای تعریف هندلر پیام با فیلتر:
```php
$bot->onMessage($bot->filterCommand("start"), function($bot) {
    $bot->chat($bot->getChatId())->message("خوش آمدید")->send();
});
$bot->run();
```
فیلترهای آماده:
- `filterText($text)`
- `filterCommand($command)`
- `filterButton($buttonId)`
- `filterChatId($chatId)`
- `filterSenderId($senderId)`

---

### دیگر امکانات
- دریافت اطلاعات خود بات: `getMe()`
- دریافت اطلاعات چت: `getChat(['chat_id' => ...])`
- دریافت آپدیت‌ها: `getUpdates()`
- مدیریت فایل و دانلود: `getFile($file_id)`, `downloadFile($file_id, $to)`
- تنظیم دستورات بات: `setCommands(['bot_commands' => ...])`
- مدیریت Endpoints: `updateBotEndpoints(['url'=>..., 'type'=>...])`

---

## کلاس‌های کمکی

### Button
نماینده یک دکمه در کیپد. متدهای استاتیک مانند:
- `Button::simple($id, $text)`
- `Button::selection($id, $title, $items)`
- `Button::calendar($id, $title, $type)`
و ...  
برای ساخت انواع دکمه (متن، انتخاب، تقویم، فایل، لینک و ...)

### KeypadRow و Keypad
- `KeypadRow`: یک ردیف از دکمه‌ها
- `Keypad`: مجموعه‌ای از ردیف‌ها و تنظیمات کیپد

---

## مثال کامل

```php
require_once 'RubikaBot.php';

use RubikaBot\Bot;
use RubikaBot\Button;
use RubikaBot\Keypad;
$bot = new Bot("TOKEN");

$bot->onMessage($bot->filterCommand("start"), function($bot) {
    $keypad = Keypad::make()
        ->addRow((new KeypadRow())->add(Button::simple("id1", "دکمه 1")));
    $bot->chat($bot->getChatId())
        ->message("خوش آمدید!")
        ->chatKeypad($keypad->toArray())
        ->send();
});

$bot->run();
```

---

## نکات مهم

- هر متد با return self قابلیت chain شدن دارد.
- پس از هر ارسال، مقادیر Builder ریست می‌شوند.
- خطاهای ورودی با Exception مدیریت می‌شوند.

---

### نویسنده
- توسعه‌دهنده: AbolfazlMirzae
