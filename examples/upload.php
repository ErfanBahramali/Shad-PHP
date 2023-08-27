<?php

/**
 * https://github.com/ErfanBahramali/Shad-PHP
 * @NabiKAZ
 */

require_once __DIR__ . '/vendor/autoload.php';

use ShadPHP\ShadPHP;

// Enter the phone number you want to login with.
$phone_number = 989123456789;

// The path and name of the file on the disk that you want to upload.
$file_name = './sample.jpg';

// Optional message. You can leave it blank.
$message = 'Hi';

// Create the main object of the program.
$account = new ShadPHP($phone_number);

// The size of download or upload chunks is in bytes.
// Depending on the size of the file and the speed, maybe a different number is better.
// You can leave the value blank to use the default value.
$account->chunkSize = 128 * 1024;

// Here "getUserGUID()" refers to the user you are logged in with.
// So here the file is saved in your Saved Messages.
$to_userid = $account->getUserGUID();

/**
 * Callback of the download or upload operation that delivers two values.
 * @param int $done Value done in bytes.
 * @param int $total Total file size in bytes.
 * Using this callback is not mandatory.
 */
$progress_cb = function ($done, $total) {
    $progress = round(($done / $total) * 100, 2);
    echo "Done: $done bytes | Total: $total bytes | Progress: $progress%\n";
};

// Start uploading the file.
$result = $account->uploadFile($to_userid, $file_name, $message, $progress_cb);

// Error handling.
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
