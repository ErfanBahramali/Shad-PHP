<?php

/**
 * https://github.com/ErfanBahramali/Shad-PHP
 * @NabiKAZ
 */

require_once __DIR__ . '/vendor/autoload.php';

use ShadPHP\ShadPHP;

//شماره تلفنی که میخواهید با آن لاگین شود را وارد کنید
$tel_number = 989123456789;

//این آرایه را نیاز نیست دستی مقداردهی کنید و در آرایه مسیج میتوانید پیدا کنید
$file_inline = [
    'file_id' => 11111111111111,
    'mime' => 'jpg',
    'dc_id' => 111,
    'access_hash_rec' => '1111111111111111111111111111111111111111',
    'file_name' => 'sample.jpg',
    'size' => 111111,
    'type' => 'File',
];

//ایجاد آبجکت اصلی برنامه
$account = new ShadPHP($tel_number);

//اندازه تکه‌های دانلود یا آپلود بر حسب بایت است
//بسته حجم فایل و سرعت شاید عدد متفاوتی بهتر باشد
//میتوانید مقدار ندهید تا از پیشفرض استفاده کند
$account->chunkSize = 128 * 1024;

//میتوانید اسم فایلی که باید ذخیره شود را تعیین کنید
//یا خالی بگذارید تا همان اسم اصلی فایل را بگیرید
$save_file = 'sample_' . time() . '.' . $file_inline['mime'];

//میتوانید تعیین کنید فایل در صورت وجود بازنویسی شود یا خیر
$overwrite = true;

//کال‌بک عملیات دانلود یا آپلود است که دو مقدار تحویل میدهد:
//پارامتر اول: مقدار انجام شده بر حسب بایت
//پارامتر دوم: حجم کل فایل بر حسب بایت
//استفاده از این کال‌بک اجباری نیست
$progress_cb = function ($done, $total) {
    $progress = round(($done / $total) * 100, 2);
    echo "Done: $done bytes | Total: $total bytes | Progress: $progress%\n";
};

//شروع دانلود فایل
$result = $account->downloadFile($file_inline, $save_file, $overwrite, $progress_cb);

//ارور هندلینگ
if (isset($result['status']) && $result['status'] === 'OK') {
    echo 'File Downloaded Successfully.' . "\n";
} else {
    echo 'Error: ' . $result['status_det'] . "\n";
}
