<?php
class ChatHandler {
    protected $auth = false;
    protected $name, $pass;
    private $html, $inputs, $headers, $languages;
    /* SETTINGS */
    const NOT_STAFF  = ['guest']; # You can use: member, mod, owner, main
    const BLACK_LIST = [10101, 1510151, 23232323, 356566558]; # Black list, you can ignore bots or someone else
    const CACHE_TIME = 86400; # 24 hours in seconds
    const PHRASES    = [
        'Invalid password.',
        'You need MANAGE power enabled.',
        'Page not found, you can use 0-5.',
        'Language not found.'
    ];
    const XAT_IDS = [
        7   => 'Darren',
        42  => 'xat',
        100 => 'Sam',
        101 => 'Chris',
        200 => 'Ajuda',
        201 => 'Ayuda',
        804 => 'Bot',
        911 => 'Guy',
    ];
    const URL = [
        'xs'   => 'https://xat.me/web_gear/chat/profile.php?id=%d',
        'edit' => 'https://xat.com/web_gear/chat.php?id=%d&pw=%d',
        'chat' => 'https://xat.com/web_gear/chat/editgroup.php?GroupName=%s',
        'eip'  => 'https://xat.com/web_gear/chat/eip.php?id=%d&pw=%d&md=4&back=%s&t=%s',
    ];
    /*-*-*-*-*-*/

    public function __construct(string $name, string $pass) {
        try {
            $this->name = $name;
            $this->pass = $pass;
            $this->headers = [
                'Referer'    => sprintf(self::URL['chat'], $this->name),
                'User-Agent' => 'Mozilla/5.0 (X11; Linux i586; rv:31.0) Gecko/20100101 Firefox/31.0',
            ];
            $this->html = $this->getInitData();
            $this->loadInputs();
        } catch (Exception $e) {
            print $e;
            exit;
        }
    }

    public function getStaffList() {       
        $this->inputs['BackupUsers'] = 1;
        $getParams = $this->submit();
        $staffList = [];
        if (strpos($getParams, '**<span data-localize=edit.manage') !== false) {
            return self::PHRASES[1];
        }
        foreach(explode(PHP_EOL, $getParams) as $line) {
            $user = explode(',', $line);
            if (!in_array($user[5], self::NOT_STAFF) && !in_array(intval($user[0]), self::BLACK_LIST)) {
                $xatUser = $this->getUsername(intval($user[0]));
                $isTemp = substr($user[5], 0, 4) == 'temp' ? true : false;
                if ($xatUser) {
                    $staffList[$user[0]] = [
                        'user' => $xatUser,
                        'rank' => str_replace('temp', '', $user[5]),
                        'temp' => $isTemp,
                    ];
                } 
            } 
                
        }
        return $staffList;
    }

    public function setOuter(string $bg) {
        $this->inputs['back'] = $bg;
        $this->saveChanges();
        return true;
    }

    public function setInner(string $bg) {
        $reqData = $this->requests(sprintf(self::URL['edit'], intval($this->inputs['id']), intval($this->inputs['pw'])));
        preg_match('/<input name="background" type="hidden" value="(.*?);=(.*?)">/is', $reqData, $getData);
        $newData = sprintf('%s;=%s', $bg, $getData[2]);
        $this->requests(sprintf(self::URL['eip'], intval($this->inputs['id']), intval($this->inputs['pw']), $newData, time()));
        return true;
    }

    public function setTransparent(bool $mode) {
        if ($mode) {
            $this->inputs['Transparent'] = 'ON';
        } else {
            unset($this->inputs['Transparent']);
        }
        $this->saveChanges();
        return true;
    }

    public function setComments(bool $mode) {
        if ($mode) {
            $this->inputs['Comments'] = 'ON';
        } else {
            unset($this->inputs['Comments']);
        }
        $this->saveChanges();
        return true;
    }

    public function setDescription(string $desc) {
        $this->inputs['GroupDescription'] = $desc;
        $this->saveChanges();
        return true;
    }

    public function setTags(string $tags) {
        $this->inputs['GroupDescription'] = $tags;
        $this->saveChanges();
        return true;
    }

    public function setAdsLink(string $url) {
        $this->inputs['www'] = $url;
        $this->saveChanges();
        return true;
    }

    public function setLanguage(string $lang) {
        if (!in_array($lang, $this->languages)) {
            return self::PHRASES[3];
        }
        $this->inputs['Lang'] = $lang;
        $this->saveChanges();
        return true;
    }

    public function setButtonText(int $number, string $text) {
        if ($number < 0 || $number > 5){
            return self::PHRASES[2];
        }
        $input = sprintf("media%d", $number);
        if (array_key_exists($input, $this->inputs)) {
            $this->inputs[$input] = $text;
        }
        $this->saveChanges();
    }

    public function setButtonName(int $number, string $title) {
        if ($number < 0 || $number > 5){
            return self::PHRASES[2];
        }
        $input = sprintf("button%d", $number);
        if (array_key_exists($input, $this->inputs)) {
            $this->inputs[$input] = $title;
        }
        $this->saveChanges();
    }

    public function saveChanges() {
        $this->inputs['submit1'] = 1;
        return $this->submit();
    }

    public function submit() {
        $getSetup = $this->requests(self::URL['chat'], $this->inputs);
        return $getSetup;
    }

    public function getUsername(int $uid) {
        $uid = intval($uid);
        if (array_key_exists($uid, self::XAT_IDS)) {
            return self::XAT_IDS[$uid];
        }
        $rUsers = file_get_contents(__DIR__ . '/usercache.json');
        $users = json_decode($rUsers, true); # not obj
        if (array_key_exists($uid, $users)) {
            if ($users[$uid]['time'] >= time()) {
                return $users[$uid]['name'];
            }
        } 
        $getProfile = $this->requests(sprintf(self::URL['xs'], $uid));
        if ($getProfile && strlen($getProfile) < 20) {
            $users[$uid] = [
                'name' => $getProfile,
                'time' => intval(time() + self::CACHE_TIME),
            ];
        }
        file_put_contents(__DIR__ . '/usercache.json', json_encode($users));
        if (array_key_exists($uid, $users)) {
            return $users[$uid]['name'];
        }
        return false;
    }

    private function getInitData() {
        $params = [
            'GroupName'  => $this->name, 
            'password'   => $this->pass, 
            'SubmitPass' => 'Submit',
        ];
        $getParams = $this->requests(sprintf(self::URL['chat'], $this->name), $params);
        if (strpos($getParams, '**<span data-localize=buy.wrongpassword>') !== false) {
            throw new Exception(self::PHRASES[0]);
        }
        $this->auth = true;
        return $getParams;
    }

    private function loadInputs() {
        if (!$this->auth) {
            return self::PHRASES[0];
        }
        $this->html = str_replace('\r\n', '', $this->html); # fixed
        preg_match_all('/<input(.*?)>/is', $this->html, $getInputs);
        preg_match('/<textarea id="media0"(.*?)>(.*?)<\/textarea>/is', $this->html, $getTextarea);
        preg_match('/<select name="Lang">(.*?)<\/select>/is', $this->html, $getLang);
        preg_match_all('/<option value="([\w]+)"(.*?)>(.*?)<\/option>/is', $getLang[0], $getLangList);
        $this->languages = $getLangList[1];
        $currentLangId = array_search(' selected', $getLangList[2]);
        $getLanguage = $this->languages[$currentLangId];
        $this->inputs['Lang'] = $getLanguage;
        $this->inputs['media0'] = $getTextarea[2];
        foreach ($getInputs[1] as $i) {
            preg_match_all('/name\="(.*?)"/', $i, $getInput);
            preg_match_all('/name\=(.*?) /', $i, $getInputLazy);
            if (!empty($getInput[1])) {
                preg_match_all('/value\="(.*?)"/is', $i, $getValue);
                preg_match_all('/ checked/', $i, $isChecked);
                if (!empty($getValue[1])) {
                    if (($getValue[1][0] == 'ON' && !empty($isChecked[1])) || $getValue[1][0] != 'ON') {
                        $this->inputs[$getInput[1][0]] = $getValue[1][0];
                    }
                }
            } else if (!empty($getInputLazy[1])) {
                preg_match_all('/value\="(.*?)"/is', $i, $getValue);
                if (!empty($getValue[1])) {
                    $this->inputs[$getInputLazy[1][0]] = $getValue[1][0];
                }
            }
        }
        return $this->inputs;
    }

    private function requests($url, $params = array()) {
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_HTTPHEADER     => $this->headers,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_POST           => (empty($params) ? false : true),
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
