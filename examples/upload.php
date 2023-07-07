<?php

/**
 * https://github.com/ErfanBahramali/Shad-PHP
 * @NabiKAZ
 */

require_once __DIR__ . '/vendor/autoload.php';

use ShadPHP\ShadPHP;

//شماره تلفنی که میخواهید با آن لاگین شود را وارد کنید
$tel_number = 989123456789;

//مسیر و اسم فایل موجود روی دیسک که قصد آپلود دارید
$file_name = './sample.jpg';

//پیام دلخواه که میتوانید مقداری وارد نکنید
$message = 'سلام';

//ایجاد آبجکت اصلی برنامه
$account = new ShadPHP($tel_number);

//اندازه تکه‌های دانلود یا آپلود بر حسب بایت است
//بسته حجم فایل و سرعت شاید عدد متفاوتی بهتر باشد
//میتوانید مقدار ندهید تا از پیشفرض استفاده کند
$account->chunkSize = 128 * 1024;

//اینجا user_guid اشاره به کاربری که با آن لاگین شده‌اید دارد
//بنابراین در اینجا فایل در سیو مسیج شما ذخیره میشود
$to_userid = $account->user_guid;

//کال‌بک عملیات دانلود یا آپلود است که دو مقدار تحویل میدهد:
//پارامتر اول: مقدار انجام شده بر حسب بایت
//پارامتر دوم: حجم کل فایل بر حسب بایت
//استفاده از این کال‌بک اجباری نیست
$progress_cb = function ($done, $total) {
    $progress = round(($done / $total) * 100, 2);
    echo "Done: $done bytes | Total: $total bytes | Progress: $progress%\n";
};

//شروع آپلود فایل
$result = $account->uploadFile($to_userid, $file_name, $message, $progress_cb);

//ارور هندلینگ
if (isset($result['status']) && $result['status'] === 'OK') {
    echo 'File Uploaded Successfully.' . "\n";
    echo 'Message ID: ' . $result['data']['message_update']['message_id'] . "\n";
    echo 'text: ' . $result['data']['message_update']['message']['text'] . "\n";
    echo 'file_id: ' . $result['data']['message_update']['message']['file_inline']['file_id'] . "\n";
    echo 'mime: ' . $result['data']['message_update']['message']['file_inline']['mime'] . "\n";
    echo 'dc_id: ' . $result['data']['message_update']['message']['file_inline']['dc_id'] . "\n";
    echo 'access_hash_rec: ' . $result['data']['message_update']['message']['file_inline']['access_hash_rec'] . "\n";
    echo 'file_name: ' . $result['data']['message_update']['message']['file_inline']['file_name'] . "\n";
    echo 'size: ' . $result['data']['message_update']['message']['file_inline']['size'] . "\n";
    echo 'type: ' . $result['data']['message_update']['message']['file_inline']['type'] . "\n";
} else {
    echo 'Error: ' . $result['status_det'] . "\n";
}
