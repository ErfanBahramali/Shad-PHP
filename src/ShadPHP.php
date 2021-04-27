<?php

/**
 * https://github.com/ErfanBahramali/Shad-PHP 
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
    private $user_guid;
    public $accountInfo;

    /** 
     * check account and login
     * @param int $phoneNumber user phone number to login
     */
    public function __construct(int $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
        $this->updateServerInfos();
        // $this->updateTexts();
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
     * @return array request response
     */
    public function login()
    {
        $response = $this->run('sendCode', [
            'phone_number' => $this->phoneNumber,
            'send_type' => 'SMS'
        ], true);
        $phone_code_hash = $response['data']['phone_code_hash'];
        $code_digits_count = $response['data']['code_digits_count'];
        $has_confirmed_recovery_email = $response['data']['has_confirmed_recovery_email'];
        getCode:
        print_r('Please Enter Code:' . PHP_EOL);
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
    public function getMessages(string $object_guid, string $sort, string $filter_type, int $max_id)
    {
        return $this->run('getMessages', ['object_guid' => $object_guid, 'sort' => $sort, 'filter_type' => $filter_type, 'max_id' => $max_id]);
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
}
