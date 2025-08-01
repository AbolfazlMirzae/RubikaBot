# RubikaBot PHP Library

کتابخانه RubikaBot یک کلاس PHP قدرتمند و شی‌گرا برای تعامل با Bot API روبیکا است. این ابزار امکانات کاملی برای کار با ربات‌های روبیکا، ارسال پیام، فایل، ساخت کیبورد، مدیریت پیام و بسیاری امکانات دیگر را فراهم می‌کند.

## شروع سریع

### نصب

کافی است فایل `RubikaBot.php` را در پروژه خود قرار دهید و آن را include کنید:

```php
require_once 'RubikaBot.php';
```

### ساخت ربات

```php
$bot = new RubikaBot('TOKEN_BOT شما');
```

### ارسال پیام

```php
$bot->sendMessage([
    'chat_id' => 'CHAT_ID',
    'text'    => 'سلام! به ربات ما خوش آمدید.'
]);
```

### ارسال فایل

```php
$bot->sendFile('CHAT_ID', '/path/to/file.jpg', 'یک عکس', null);
```

### دریافت اطلاعات حساب ربات

```php
$info = $bot->getMe();
print_r($info);
```

---

## مستندات متدها

### پیام‌رسانی

- **sendMessage(array $data)**
  - پارامترها: `chat_id`, `text`, مقادیر اختیاری مثل `inline_keypad`
  - ارسال پیام متنی به چت

- **sendFile(string $chat_id, string $file_path, ?string $caption = null, ?array $keypad = null)**
  - ارسال فایل (عکس، ویدیو، موزیک، PDF و ...)
  - محدودیت حجم: ۵۰ مگابایت

- **sendPoll(array $data)**
  - نظرسنجی با حداقل ۲ گزینه

- **sendLocation(array $data)**
  - ارسال لوکیشن (پارامترها: `chat_id`, `latitude`, `longitude`)

- **sendContact(array $data)**
  - ارسال مخاطب (پارامترها: `chat_id`, `first_name`, `phone_number`)

---

### مدیریت پیام

- **editMessageText(array $data)**
  - ویرایش متن پیام

- **editMessageKeypad(array $data)**
  - ویرایش کیبورد اینلاین پیام

- **deleteMessage(array $data)**
  - حذف پیام

- **forwardMessage(array $data)**
  - فوروارد پیام

---

### مدیریت چت

- **getChat(array $data)**
  - دریافت اطلاعات چت

- **editChatKeypad(array $data)**
  - ویرایش کیبورد چت

- **removeChatKeypad(string $chat_id)**
  - حذف کیبورد معمولی

- **removeInlineKeypad(string $chat_id, string $message_id)**
  - حذف کیبورد اینلاین از پیام

---

### دریافت آپدیت و فایل

- **getUpdates(array $data = [])**
  - دریافت آپدیت‌های جدید ربات

- **getFile($file_id)**
  - دریافت لینک دانلود فایل با آی‌دی

- **downloadFile($file_id, $to)**
  - دانلود فایل و ذخیره در مسیر دلخواه

---

### ساخت کیبورد و دکمه

#### توابع ساخت دکمه و کیبورد برای ارسال به عنوان `inline_keypad` یا `chat_keypad`:

- **makeSimpleButton($id, $text)**
- **makeSelectionButton($id, $title, $items, $multi = false, $columns = 1)**
- **makeCalendarButton($id, $title, $type = "DatePersian")**
- **makeTextboxButton($id, $title, $lineType = "SingleLine", $keypadType = "String")**
- **makeLocationButton($id, $title, $type = "Picker")**
- **makeNumberPickerButton($id, $title, $min, $max, $default = null)**
- **makeStringPickerButton($id, $title, $items, $default = null)**
- **makeKeypadRow($buttons)**
- **makeKeypad($rows, $resize = true, $onetime = false)**

**نمونه ساخت کیبورد:**

```php
$rows = [
    RubikaBot::makeKeypadRow([
        RubikaBot::makeSimpleButton('btn1', 'دکمه ۱'),
        RubikaBot::makeSimpleButton('btn2', 'دکمه ۲'),
    ]),
];
$keypad = RubikaBot::makeKeypad($rows);
$bot->sendMessage([
    'chat_id' => $chat_id,
    'text' => 'انتخاب کنید:',
    'inline_keypad' => $keypad
]);
```

---

## اطلاعات بروزرسانی (Webhook/Update)

برای دریافت اطلاعات پیام ورودی:

```php
$update = $bot->getUpdate();
$type = $bot->getUpdateType();
$chat_id = $bot->getChatId();
$text = $bot->getText();
$file_id = $bot->getFileId();
```

---

## پیکربندی

در سازنده امکان تنظیم موارد زیر وجود دارد:
- `timeout` (پیش‌فرض 30 ثانیه)
- `max_retries` (تعداد تکرار درخواست در صورت خطا، پیش‌فرض 3)
- `parse_mode` (Markdown یا ...)

```php
$bot = new RubikaBot('TOKEN', [
    'timeout' => 20,
    'max_retries' => 5,
    'parse_mode' => 'Markdown'
]);
```

---

## خطاها و مدیریت استثنا

در صورت بروز خطا مثل پارامتر ناقص یا فایل نامعتبر، متدها یک Exception از نوع `InvalidArgumentException` یا `RuntimeException` پرتاب می‌کنند. بنابراین پیشنهاد می‌شود تمام فراخوانی‌ها درون try/catch قرار گیرد:

```php
try {
    $bot->sendMessage([...]);
} catch (Exception $e) {
    echo "خطا: " . $e->getMessage();
}
```

---

## منابع بیشتر

- [سند رسمی Bot API روبیکا](https://rubika.ir/botapi)
- [لیست متدها](https://rubika.ir/botapi/methods)
- [مدل‌های داده](https://rubika.ir/botapi/models)

---

## لایسنس

این پروژه برای استفاده شخصی و غیرتجاری آزاد است.

---

**توسعه‌دهنده:**  
[ابوالفضل میرزائی]
