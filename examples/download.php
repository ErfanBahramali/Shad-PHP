<?php

/**
 * https://github.com/ErfanBahramali/Shad-PHP
 * @NabiKAZ
 */

require_once __DIR__ . '/vendor/autoload.php';

use ShadPHP\ShadPHP;

// Enter the phone number you want to login with
$phone_number = 989123456789;

// You don't need to set this array manually and you can find it in the message array
$file_inline = [
    'file_id' => 11111111111111,
    'mime' => 'jpg',
    'dc_id' => 111,
    'access_hash_rec' => '1111111111111111111111111111111111111111',
    'file_name' => 'sample.jpg',
    'size' => 111111,
    'type' => 'File',
];

// Create the main application object
$account = new ShadPHP($phone_number);

// Size of download or upload chunks in bytes
// Depending on your file size and speed, a different number may be better.
// You can not enter a value to use the default value
$account->chunkSize = 128 * 1024;

// You can specify the name of the file to be saved
// Or leave it blank to get the same original file name
$save_file = 'sample_' . time() . '.' . $file_inline['mime'];

// You can determine if the file is overwritten or not
$overwrite = true;

/**
 * Callback of the download or upload operation that delivers two values
 * @param int $done value done in bytes
 * @param int $total Total file size in bytes
 * Using this callback is not mandatory
 */
$progress_cb = function ($done, $total) {
    $progress = round(($done / $total) * 100, 2);
    echo "Done: $done bytes | Total: $total bytes | Progress: $progress%\n";
};

// Start downloading the file
$result = $account->downloadFile($file_inline, $save_file, $overwrite, $progress_cb);

// Error handling
if (isset($result['status']) && $result['status'] === 'OK') {
    echo 'File Downloaded Successfully.' . "\n";
} else {
    echo 'Error: ' . $result['status_det'] . "\n";
}
