<?php

/**
 * https://github.com/ErfanBahramali/Shad-PHP
 * @Author: Erfan Bahramali (twitter.com/Erfan_Bahramali)
 * @Developed: Nabi KaramAliZadeh (twitter.com/NabiKAZ) 
 */

namespace ShadPHP;

class ShadPHP
{
    private $phoneNumber;
    private $configFile = 'shad.config';
    private $client =  [
        'app_name' => 'Main',
        'app_version' => '3.1.15',
        'platform' => 'Web',
        'package' => 'web.shad.ir',
        'lang_code' => 'fa'
    ];
    public $serverInfos;
    public $texts;
    private $api_version = '5';
    private $auth;
    private $encryptKey;
    public $user_guid;
    public $accountInfo;
    public $chunkSize = 128 * 1024;
    public $maxAttempts = 5;
    public $sleep = 2;
    public $debug = false;

    /** 
     * check account and login
     * @param int $phoneNumber user phone number to login
     */
    public function __construct(int $phoneNumber = null, string $configFile = null)
    {
        set_time_limit(0);
        $this->updateServerInfos();
        if (!isset($phoneNumber)) return;
        $this->phoneNumber = $phoneNumber;
        // $this->updateTexts();
        if (isset($configFile)) $this->configFile = $configFile;
        $this->loadConfig();
        $response = $this->getUserInfo($this->user_guid);
        if (isset($response['status_det']) && $response['status'] == 'ERROR_ACTION' && $response['status_det'] == 'NOT_REGISTERED') {
            $this->login();
        } else {
            $this->accountInfo = $response['data']['user'];
        }
    }

    /** 
     * set datas of config file
     * @param array $datas new datas of config
     * @return bool The function returns the number of bytes that were written to the config file, or false on failure.
     */
    private function setConfig(array $datas = [])
    {
        $config = json_decode(base64_decode(file_get_contents($this->configFile)), true);
        if (isset($datas['auth'])) {
            $datas['encryptKey'] = crypto::createSecretPassphrase($datas['auth']);
            $this->auth = $datas['auth'];
            $this->encryptKey = $datas['encryptKey'];
        }
        if (isset($datas['user_guid'])) {
            $this->user_guid = $datas['user_guid'];
        }
        $config[$this->phoneNumber] = array_merge($config[$this->phoneNumber], $datas);
        return file_put_contents($this->configFile, base64_encode(json_encode($config)));
    }

    /** 
     * load data of config file and set variabels values
     * @return bool true
     */
    private function loadConfig()
    {
        if (file_exists($this->configFile)) {
            $config = json_decode(base64_decode(file_get_contents($this->configFile)), true);
            if (isset($config[$this->phoneNumber])) {
                $configs = $config[$this->phoneNumber];
                $auth = $configs['auth'];
                $encryptKey = $configs['encryptKey'];
                $user_guid = (isset($configs['user_guid'])) ? $configs['user_guid'] : '';
            } else {
                $auth = crypto::azRand();
                $encryptKey = crypto::createSecretPassphrase($auth);
                $user_guid = '';
                $config[$this->phoneNumber] = [
                    'auth' => $auth,
                    'encryptKey' => $encryptKey,
                ];
                file_put_contents($this->configFile, base64_encode(json_encode($config)));
            }
        } else {
            $auth = crypto::azRand();
            $encryptKey = crypto::createSecretPassphrase($auth);
            $user_guid = '';
            file_put_contents($this->configFile, base64_encode(json_encode([$this->phoneNumber => [
                'auth' => $auth,
                'encryptKey' => $encryptKey,
            ]])));
        }
        $this->auth = $auth;
        $this->encryptKey = $encryptKey;
        $this->user_guid = $user_guid;
        return true;
    }

    /** 
     * @param string $keyName key of array index to get
     * @return * $keyName index data
     */
    public function getConfig(string $keyName)
    {
        return json_decode(base64_decode(file_get_contents($this->configFile)), true)[$keyName];
    }

    /** 
     * send http request to site
     * @param string $url request url
     * @param array $data request payload(data)
     * @param bool $jsonDecode default json decode result 
     * @param bool $setHeader set header or not set header
     * @return array|string if $decode is true return decoded array else return normal response
     */
    public function request(string $url, array $data = [], bool $jsonDecode = true, bool $isJsonData = true)
    {
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            // CURLOPT_VERBOSE => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; rv:78.0) Gecko/20100101 Firefox/78.0',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate, br',
                'Referer: https://web.shad.ir/',
                'Origin: https://web.shad.ir',
                'Connection: keep-alive',
            ],
        ];
        if ($data !== []) {
            $options[CURLOPT_POST] = true;
            if ($isJsonData) {
                $options[CURLOPT_HTTPHEADER][] =  'Content-Type: application/json';
                $data = json_encode($data);
            } else {
                $data = http_build_query($data);
            }
            $options[CURLOPT_POSTFIELDS] = $data;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);
        return ($jsonDecode) ? json_decode($response, true) : $response;
    }

    /** 
     * send option request for before main request
     * @param string $url request url
     * @return int request http code response
     */
    public function sendOptionRequest(string $url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'OPTIONS',
            CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; rv:78.0) Gecko/20100101 Firefox/78.0',
                'Accept: */*',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate, br',
                'Access-Control-Request-Method: POST',
                'Access-Control-Request-Headers: content-type',
                'Referer: https://web.shad.ir/',
                'Origin: https://web.shad.ir',
                'Connection: keep-alive',
            ],
            CURLOPT_NOBODY => true,
            // CURLOPT_VERBOSE => true,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode;
    }

    /** 
     * @param array $data request datas 
     * @param bool $setTmpSession if true set tmp_session key name for auth of request else set auth
     * @return array request response
     */
    public function sendRequest(array $data, bool $setTmpSession = false)
    {
        $default_api_urls = $this->serverInfos['default_api_urls'];
        $data['client'] = $this->client;
        foreach ($default_api_urls as $url) {
            $responseHttpCode = $this->sendOptionRequest($url);
            if ($responseHttpCode === 200) {
                $requestData = [
                    'api_version' => $this->api_version,
                    'data_enc' => crypto::aes_256_cbc_encrypt(json_encode($data), $this->encryptKey),
                ];
                $dataKeyName = (!$setTmpSession) ? 'auth' : 'tmp_session';
                $requestData[$dataKeyName] = $this->auth;
                $response = $this->request($url, $requestData);

                if (is_array($response)) {
                    if (isset($response['data_enc'])) {
                        $response = $this->decryptRequest($response['data_enc']);
                    }
                    return json_decode($response, true);
                }
            }
        }
    }

    /** 
     * run sendRequest function more comfortable
     * @param string $method 
     * @param array $input datas
     * @param bool $setTmpSession if true set tmp_session key name for auth of request else set auth
     * @return array request response
     */
    public function run(string $method, array $input = [], bool $setTmpSession = false)
    {
        return $this->sendRequest([
            'method' => $method,
            'input' => $input,
        ], $setTmpSession);
    }

    /** 
     * decrypt request 
     * @param string $data_enc request data
     * @param string $auth optional tmpSession or auth of request if empty use this class encryptkey
     * @return string decrypted data as text
     */
    public function decryptRequest(string $data_enc, string $auth = '')
    {
        return crypto::aes_256_cbc_decrypt($data_enc, (empty($auth) ? $this->encryptKey : crypto::createSecretPassphrase($auth)));
    }

    /** 
     * Login in to account
     * @return bool logined or not
     */
    public function login()
    {
        $response = $this->run('sendCode', [
            'phone_number' => $this->phoneNumber,
            'send_type' => 'SMS'
        ], true);
        if (isset($response['status']) && $response['status'] !== 'OK') {
            print_r("Error: " . $response['status_det'] . PHP_EOL);
            return false;
        }
        $phone_code_hash = $response['data']['phone_code_hash'];
        $code_digits_count = $response['data']['code_digits_count'];
        $has_confirmed_recovery_email = $response['data']['has_confirmed_recovery_email'];
        getCode:
        echo 'Please Enter Code (+' . $this->phoneNumber . ') : ';
        $code = readline();
        if (is_numeric($code) && strlen($code) == $code_digits_count) {
            $response = $this->run('signIn', [
                'phone_number' => $this->phoneNumber,
                'phone_code_hash' => $phone_code_hash,
                'phone_code' => $code
            ], true);
            $responseData = $response['data'];
            if ($responseData['status'] === 'OK') {
                $auth = $responseData['auth'];
                $user_guid = $responseData['user']['user_guid'];
                $this->setConfig([
                    'auth' => $auth,
                    'user_guid' => $user_guid,
                ]);
                $this->accountInfo = $responseData['user'];
                $this->registerDevice();
                return true;
            } else {
                /* 
                    CodeIsInvalid
                    CodeIsExpired
                 */
                if ($responseData['status'] == 'CodeIsExpired') {
                    print_r("Code Is Expired" . PHP_EOL);
                } else {
                    print_r("Code Is Invalid" . PHP_EOL);
                }
                goto getCode;
            }
        } elseif (!isset($code) || $code == '') {
            // for exit and cancel login
            exit();
        } else {
            print_r("Code Is Invalid" . PHP_EOL);
            goto getCode;
        }
    }

    /** 
     * @return int Browser Id code
     */
    public function getBrowserId()
    {
        // Mozilla/5.0 (Windows NT 10.0; rv:78.0) Gecko/20100101 Firefox/78.0
        return '45010078020100101780';
    }

    /** 
     * register Device as session
     * @return array response
     */
    public function registerDevice()
    {
        return $this->run('registerDevice', [
            "token_type" => 'Web',
            'token' => '',
            'app_version' => 'WB_3.1.15',
            'lang_code' => 'fa',
            'system_version' => 'Linux',
            'device_model' => 'Firefox 78',
            'device_hash' => $this->getBrowserId()
        ]);
    }

    /** 
     * log out of account
     * @return array
     */
    public function logout()
    {
        return $this->run('logout');
    }

    /** 
     * update $serverInfos value
     */
    public function updateServerInfos()
    {
        for ($i = 0; $i < 5; $i++) {
            $result = $this->request('https://shgetdcmess.iranlms.ir/', [
                'api_version' => '4',
                'method' => 'getDCs',
                'client' => $this->client
            ], true, false);

            if (isset($result['status']) && $result['status'] === 'OK') {
                $this->serverInfos = $result['data'];
                return true;
            }
            sleep(mt_rand(4, 8));
        }
        return false;
    }

    /** 
     * update $texts value 
     */
    public function updateTexts()
    {
        $this->texts = $this->request('https://web.shad.ir/assets/locales/fa-ir.json?v=3.1.15');
    }

    /** 
     * on update call function
     * @param callback $callback function call on update
     */
    public function onUpdate(callable $callback)
    {
        while (true) {
            $default_sockets = $this->serverInfos['default_sockets'];
            foreach ($default_sockets as $socket) {
                // echo "$socket\n";
                try {
                    $client = new \WebSocket\Client($socket, [
                        'headers' => [ // Additional headers, used to specify subprotocol
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; rv:78.0) Gecko/20100101 Firefox/78.0',
                            'origin' => 'https://web.shad.ir',
                        ],
                        'timeout' => 60, // 1 minute time out
                    ]);
                    $client->text(json_encode([
                        'api_version' => '5',
                        'auth' => $this->auth,
                        'data' => '',
                        'method' => 'handShake'
                    ]));
                    $timeSecCount = 0;
                    echo "Connect {$socket}\n";
                    while (true) {
                        if (($timeSecCount % 15) === 0) {
                            $client->text('{}');
                        }
                        sleep(1);
                        $timeSecCount++;
                        $message = json_decode($client->receive(), true);
                        if (isset($message['data_enc'])) {
                            $message['data_enc'] = json_decode($this->decryptRequest($message['data_enc']), true);
                        }
                        $callback($message);
                    }
                } catch (\WebSocket\ConnectionException $e) {
                    // Possibly log errors
                    // print_r($e);
                }
                // $client->close();
            }
            $this->updateServerInfos();
        }
    }

    /** 
     * get all of chats
     * @return array on success return all of chats
     */
    public function getChats()
    {
        return $this->run('getChats');
    }

    /** 
     * get all of chats ads
     * @return array on success return  all of chat ads
     */
    public function getChatAds()
    {
        return $this->run('getChatAds');
    }

    /** 
     * get new messages
     * @return array
     */
    public function getChatsUpdates()
    {
        return $this->run('getChatsUpdates', ['state' => time()]);
    }

    /** 
     * get account sessions
     * @return array 
     */
    public function getMySessions()
    {
        return $this->run('getMySessions');
    }

    /** 
     * @param string $user_guid user_guid of user to get info
     * @return array user info request response
     */
    public function getUserInfo(string $user_guid)
    {
        return $this->run('getUserInfo', ["user_guid" => $user_guid]);
    }

    /** 
     * @param string $group group to get info
     * @return array group info request response
     */
    public function getGroupInfo(string $group_guid)
    {
        return $this->run('getGroupInfo', ["group_guid" => $group_guid]);
    }

    /** 
     * @param string $channel channel to get info
     * @return array channel info request response
     */
    public function getChannelInfo(string $channel_guid)
    {
        return $this->run('getChannelInfo', ["channel_guid" => $channel_guid]);
    }

    /** 
     * @param array $objects_guids
     * @return array
     */
    public function getAbsObjects(array $objects_guids)
    {
        return $this->run('getAbsObjects', ["objects_guids" => $objects_guids]);
    }

    /** 
     * get message interval
     * @param string $object_guid
     * @param int $middle_message_id message id of message
     * @return array
     */
    public function getMessagesInterval(string $object_guid, int $middle_message_id)
    {
        return $this->run('getMessagesInterval', ['object_guid' => $object_guid, 'middle_message_id' => $middle_message_id]);
    }

    /** 
     * get update of messages
     * @param string $object_guid 
     * @return array
     */
    public function getMessagesUpdates(string $object_guid)
    {
        return $this->run('getMessagesUpdates', ['object_guid' => $object_guid, 'state' => time()]);
    }

    /** 
     * get message by filter
     * @param string $object_guid
     * @param string $sort @example FromMax 
     * @param string $filter_type type of content @example Media Music Voice File
     * @param int $max_id max message id @example 76213478446577
     * @return array
     */
    public function getMessages(string $object_guid, string $sort = 'FromMax', string $filter_type = null, int $max_id = null)
    {
        $input = ['object_guid' => $object_guid, 'sort' => $sort];
        if (isset($filter_type)) $input['filter_type'] = $filter_type;
        if (isset($max_id)) $input['max_id'] = $max_id;
        return $this->run('getMessages', $input);
    }

    /** 
     * get message by message id
     * @param string $object_guid
     * @param array $message_ids
     * @return array
     */
    public function getMessagesByID(string $object_guid, array $message_ids)
    {
        return $this->run('getMessagesByID', ['object_guid' => $object_guid, 'message_ids' => $message_ids]);
    }

    /** 
     * get status of poll
     * @param string $poll_id
     * @return array
     */
    public function getPollStatus(string $poll_id)
    {
        return $this->run('getPollStatus', ['poll_id' => $poll_id]);
    }

    /** 
     * @param array $seen_list list of message seened ['object_guid' => 'middle_message_id']
     * @return array 
     */
    public function seenChats(array $seen_list)
    {
        return $this->run('seenChats', ['seen_list' => $seen_list]);
    }

    /** 
     * search text for a special chat
     * @param string $search_text text for search
     * @param string $type @example Text
     * @param string $object_guid grop or user or channel or ... id for search
     * @return array
     */
    public function searchChatMessages(string $search_text, string $type, string $object_guid)
    {
        return $this->run('searchChatMessages', ['search_text' => $search_text, 'type' => $type, 'object_guid' => $object_guid]);
    }

    /** 
     * search text global to find user channel group or ... 
     * @param string $search_text text for search
     * @return array
     */
    public function searchGlobalObjects(string $search_text)
    {
        return $this->run('searchGlobalObjects', ['search_text' => $search_text]);
    }

    /** 
     * @param string $search_text text for search
     * @param string $type @example Text
     * @return array 
     */
    public function searchGlobalMessages(string $search_text, string $type)
    {
        return $this->run('searchGlobalMessages', ['search_text' => $search_text, 'type' => $type]);
    }

    /** 
     * @param string $object_guid send message to object_guid
     * @param string $text text to send
     * @return array request response
     */
    public function sendMessage(string $object_guid, string $text)
    {
        return $this->run('sendMessage', ['object_guid' => $object_guid, 'rnd' => mt_rand(100000, 999999), 'text' => $text]);
    }

    /** 
     * @param string $poll_id
     * @param string $selection_index index of vote
     * @return array request response
     */
    public function votePoll(string $poll_id, int $selection_index)
    {
        return $this->run('votePoll', ['poll_id' => $poll_id, 'selection_index' => $selection_index]);
    }

    /** 
     * Upload File
     * 
     * @param string $object_guid The ID of the chat where you want the file to be uploaded.
     * @param string $file_path The Path and file name in local storage.
     * @param string $text (optional) The text for file
     * @param callable $progress_cb (optional) A callback function to track the progress. params: done, total.
     * @return array Request response
     */
    public function uploadFile(string $object_guid, string $file_path, string $text = '', callable $progress_cb = null)
    {
        if (!file_exists($file_path)) {
            return ['status' => 'ERROR', 'status_det' => 'File not found.'];
        }

        $chunk_size = $this->chunkSize;
        $max_attempts = $this->maxAttempts;
        $sleep = $this->sleep;
        $file_name = pathinfo($file_path, PATHINFO_BASENAME);
        $file_size = filesize($file_path);
        $file_mime = pathinfo($file_path, PATHINFO_EXTENSION);
        $file_handle = fopen($file_path, "rb");
        $total_parts = ceil($file_size / $chunk_size);

        $result = $this->run('requestSendFile', ['file_name' => $file_name, 'size' => $file_size, 'mime' => $file_mime]);
        if (!isset($result['status']) || $result['status'] !== "OK") {
            return ['status' => 'ERROR', 'status_det' => 'Error during preparation.'];
        }
        $file_id = $result['data']['id'];
        $dc_id = $result['data']['dc_id'];
        $access_hash_send = $result['data']['access_hash_send'];
        $upload_url = $result['data']['upload_url'];

        for ($part_number = 1; $part_number <= $total_parts; $part_number++) {

            $start = ($part_number - 1) * $chunk_size;
            $end = min($start + $chunk_size, $file_size);

            if ($this->debug) {
                echo "$part_number / $total_parts  |   $start ~ $end\n";
            }

            $headers = [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; rv:78.0) Gecko/20100101 Firefox/78.0',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate, br',
                'Referer: https://web.shad.ir/',
                'Origin: https://web.shad.ir',
                'Connection: keep-alive',
                'Host: ' . parse_url($upload_url, PHP_URL_HOST),
                'access-hash-send: ' . $access_hash_send,
                'auth: ' . $this->auth,
                'file-id: ' . $file_id,
                'part-number: ' . $part_number,
                'total-part: ' . $total_parts,
                'chunk-size: ' . ($end - $start),
                'Content-Length: ' . ($end - $start),
            ];

            $curl_options = [
                CURLOPT_URL => $upload_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => fread($file_handle, $end - $start),
            ];

            $ch = curl_init();
            curl_setopt_array($ch, $curl_options);

            $attempts = 0;
            do {
                $response = curl_exec($ch);
                curl_close($ch);

                if (isset($response)) {
                    $response = json_decode($response, true);
                }

                if (isset($response['status']) && $response['status'] === 'OK') {
                    break;
                }

                $attempts++;

                if ($attempts <= $max_attempts) {
                    echo 'Waiting ' . $sleep . ' seconds... Retry ' . $attempts . '/' . $max_attempts . "\n";
                    sleep($sleep);
                }
            } while ($attempts <= $max_attempts);

            if (!isset($response['status']) || $response['status'] !== 'OK') {
                return ['status' => 'ERROR', 'status_det' => 'Connection error.'];
            }

            if (is_callable($progress_cb)) {
                $progress_cb($end, $file_size);
            }
        }

        fclose($file_handle);

        if (!isset($response['status']) || $response['status'] !== 'OK' || !isset($response['data']['access_hash_rec'])) {
            return ['status' => 'ERROR', 'status_det' => 'Access code not received.'];
        }

        $access_hash_rec = $response['data']['access_hash_rec'];

        $data = [
            'object_guid' => $object_guid,
            'rnd' => mt_rand(100000, 999999),
            'text' => $text,
            'file_inline' => [
                "dc_id" => $dc_id,
                "file_id" =>  $file_id,
                "type" => "File",
                "file_name" =>  $file_name,
                "size" => $file_size,
                "mime" => $file_mime,
                "access_hash_rec" =>  $access_hash_rec,
            ]
        ];
        return $this->run('sendMessage', $data);
    }

    /** 
     * Download File
     * You don't need to login and enter all the parameters to download!
     * Only these items in the $file_inline array are required: access_hash_rec, dc_id, size
     * 
     * @param array $file_inline File specification array including file_id, dc_id, access_hash_rec, etc...
     * @param string $save_file (optional) The name of the file you want to save.
     * @param bool $overwrite (optional) Overwrite status on the file.
     * @param callable $progress_cb (optional) A callback function to track the progress. params: done, total.
     * @return array Request response
     */
    public function downloadFile(array $file_inline, string $save_file = '', $overwrite = true, callable $progress_cb = null)
    {
        if (!isset($this->serverInfos['storages'])) {
            return ['status' => 'ERROR', 'status_det' => 'File storages not found.'];
        }
        $storages = $this->serverInfos['storages'];

        $chunk_size = $this->chunkSize;
        $max_attempts = $this->maxAttempts;
        $sleep = $this->sleep;
        $file_id = isset($file_inline['file_id']) ? $file_inline['file_id'] : '1';
        $file_mime = isset($file_inline['mime']) ? $file_inline['mime'] : '';
        $dc_id = $file_inline['dc_id'];
        $access_hash_rec = $file_inline['access_hash_rec'];
        $file_name = $save_file ? $save_file : (isset($file_inline['file_name']) ? $file_inline['file_name'] : $access_hash_rec . ($file_mime ? '.' . $file_mime : ''));
        $file_size = $file_inline['size'];
        $file_type = isset($file_inline['type']) ? $file_inline['type'] : '';
        $total_parts = ceil($file_size / $chunk_size);
        $download_url = $storages[$dc_id];

        if (!isset($download_url) || !$download_url) {
            return ['status' => 'ERROR', 'status_det' => 'Storage not found.'];
        }

        if (!$overwrite && file_exists($file_name)) {
            return ['status' => 'ERROR', 'status_det' => 'The file exists.'];
        }
        $file_handle = fopen($file_name, 'w');

        for ($part_number = 1; $part_number <= $total_parts; $part_number++) {

            $start = ($part_number - 1) * $chunk_size;
            $end = min($start + $chunk_size, $file_size);

            if ($this->debug) {
                echo "$part_number / $total_parts  |   $start ~ $end\n";
            }

            $headers = [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; rv:78.0) Gecko/20100101 Firefox/78.0',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate, br',
                'Referer: https://web.shad.ir/',
                'Origin: https://web.shad.ir',
                'Connection: keep-alive',
                'Host: ' . parse_url($download_url, PHP_URL_HOST),
                'access-hash-rec: ' . $access_hash_rec,
                'auth: ' . $this->auth,
                'file-id: ' . $file_id,
                'start-index: ' . $start,
                'last-index: ' . ($end - 1),
                'Content-Type: text/plain',
            ];

            $curl_options = [
                CURLOPT_URL => $download_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => '',
            ];

            $ch = curl_init();
            curl_setopt_array($ch, $curl_options);

            $attempts = 0;
            do {
                $response = curl_exec($ch);
                $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($status_code === 200) {
                    fwrite($file_handle, $response);
                    break;
                }

                $attempts++;

                if ($attempts <= $max_attempts) {
                    echo 'Waiting ' . $sleep . ' seconds... Retry ' . $attempts . '/' . $max_attempts . "\n";
                    sleep($sleep);
                }
            } while ($attempts <= $max_attempts);

            if ($status_code !== 200) {
                fclose($file_handle);
                unlink($file_name);
                return ['status' => 'ERROR', 'status_det' => 'Connection error.'];
            }

            if (is_callable($progress_cb)) {
                $progress_cb($end, $file_size);
            }
        }

        fclose($file_handle);

        return ['status' => 'OK', 'status_det' => 'The download was done successfully.'];
    }
}
