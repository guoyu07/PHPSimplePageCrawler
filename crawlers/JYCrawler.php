<?php
include_once __DIR__.'/../../vendor/autoload.php';

use Predis\Client;

class JYCrawler extends Crawler {

    private $interval = 2000;

    public $name = 'jy';

    private $conf;

    private $pdo;

    private $redis;

    public function __construct() {
        $fp = fopen(__DIR__.'/JYCrawler.json', 'r');
        if ($fp === false) {
            throw new Exception(__DIR__.'/JYCrawler.json can not be open');
        }

        $content = fread($fp, 1000);

        $conf = json_decode($content, true);

        function checkConf($conf) {
            if (isset(
                $conf['dbHost'],
                $conf['dbName'],
                $conf['dbPort'],
                $conf['dbUser'],
                $conf['dbPassword'],
                $conf['redisHost'],
                $conf['redisPort'],
                $conf['redisUsePasswd'],
                $conf['redisPassword'],
                $conf['redisChannel'],
                $conf['interval']
            )) return true;

            return false;
        }

        if ($conf === false || !checkConf($conf)) {
            throw new Exception($this->name.' configure file parse error');
        }

        $this->interval = $conf['interval'];

        $this->conf = $conf;

        $this->pdo = new PDO(sprintf("mysql:host=%s;dbname=%s;port=%d", $conf['dbHost'], $conf['dbName'], $conf['dbPort']), $conf['dbUser'], $conf['dbPassword']);

        $redisConfig = [
            'scheme' => 'tcp',
            'host'   => $conf['redisHost'],
            'port'   => $conf['redisPort'],
        ];
        if ($conf['redisUsePasswd']) {
            $redisConfig['password'] = $conf['redisPassword'];
        }
        $this->redis = new Client($redisConfig);
    }

    public function setConfig(){
        $this->url = '';
    }

    public function getContent() {
        $content = file_get_contents($this->url);
        return $content === false ? '' : iconv('gb2312', 'utf-8', $content) ;
    }

    public function parse($content) {
        $tmp = explode(PHP_EOL, $content);
        $result = [];
        foreach($tmp as $v){
            if (!empty($v) && strpos($v, ',')) {
                array_push($result, $v);
            }
        }
        return empty($result) ? [] : $result;
    }

    public function each($obj){
        $tmp = explode(',', $obj);
        if (empty($tmp)) {
            return 'item parse fail'.PHP_EOL;
        }

        $result = [];
        $result['code'] = $tmp[1];
        $result['name'] = $tmp[0];
        $result['last'] =  ($tmp[7]);
        $result['high'] = $tmp[5];
        $result['low'] = $tmp[6];
        $result['open'] = $tmp[4];
        $result['lastClose'] = $tmp[2];
        $result['volume'] = 1;
        $result['quoteTime'] = $tmp[39];
        $result['swing'] = $tmp[13];
        $result['swingRate'] = $result['swing'] / $tmp[3];

        return parent::each($resStr ? $resStr : '');
    }

    public function getInterval() {
        return $this->interval;
    }
}