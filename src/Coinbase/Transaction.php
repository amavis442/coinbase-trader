<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Coinbase;
use Symfony\Component\Console\Output\OutputInterface;
/**
 * Description of Transactions
 *
 * @author patrickteunissen
 */
class Transaction
{

    protected $redis;
    private $transactions;

    /**
     * 
     */
    public function __construct(OutputInterface $output)
    {
        $this->transactions = array();
        $this->output = $output;
    }

    /**
     * 
     */
    public function init()
    {
        if (REDIS_SERVER) {
            $this->redis = new Predis\Client(array(
                'scheme' => 'tcp',
                'host' => REDIS_SERVER,
                'port' => REDIS_PORT,
                'database' => REDIS_DB,
                'password' => REDIS_PASS
            ));
        } else {
            $this->redis = null;
        }
    }

    /**
     * Loads the current transactions
     */
    public function load()
    {
        //clearing transactions array so we don't have any legacy data
        $this->transactions = array();

        if (!is_null($this->redis)) {
            $this->migrateFromJsonToRedis();
            $this->readTransactionDataFromRedis();
        } else {
            $this->readTransactionDataFromJsonFile();
        }
    }

    /**
     * 
     */
    public function getAll()
    {
        $this->transactions = array();

        $this->traderID = CURRENCY . ' - ' . CRYPTO;

        //load previous data
        $this->load();

        return $this->transactions;
    }

    public function find($id)
    {
        return $this->transactions[$id];
    }
    
    /**
     * Add an transaction
     * 
     * @param array $transaction
     * @param type $msg
     */
    public function add(Array $transaction, $msg = '')
    {
        $this->load();

        $id = 1;
        if (is_array($this->transactions)) {
            $id = max(array_keys($this->transactions)) + 1;
        }

        $this->transactions[$id] = $transaction;
        $this->output->writeln($msg);

        $this->save();
        
        return $id;
    }

    /**
     * 
     * @param float $eur
     * @param float $buyat
     * @param float $sellat
     */
    public function addBuyTransaction($eur, $buyat, $sellat)
    {
        $msg = "[i] Adding BUY order for $eur " . CURRENCY . " in " . CRYPTO . " when price is <= $buyat " . CURRENCY;

        $this->add(array('eur' => $eur, 'buyprice' => $buyat, 'sellat' => $sellat), $msg);
    }

    /**
     * 
     * @param float $eur
     * @param float $sellat
     */
    public function addSellTransaction($eur, $sellat)
    {
        $msg = "[i] Adding SELL order for $eur " . CURRENCY . " in " . CRYPTO . " when price is >= $sellat " . CURRENCY;

        $this->add(array('eur' => $eur, 'sellat' => $sellat), $msg);
    }

    /**
     * 
     * @param type $sellPrice
     */
    public function listTransactions($sellPrice)
    {
        $this->load(); //update transactions since the data could have changed by now

        if (count($this->transactions) < 1) {
            $message = "No transactions at the moment\n";
        } else {

            foreach ($this->transactions as $id => $td) {
                $btc = $td['btc'];
                $eur = $td['eur'];

                $buyprice = $td['buyprice'];
                $sellat = $td['sellat'] + $eur;

                $newprice = $btc * $sellPrice;
                $diff = round(($sellPrice - $buyprice) * $btc, 2);

                $message = "ID: $id\t";

                //is this a SELL order?
                if (!$buyprice) {
                    $message .= "SELL order for $eur " . CURRENCY . " when " . CRYPTO . " will reach a price of " . $td['sellat'] . " " . CURRENCY . "\n";
                } else {
                    //is this a BUY order?
                    if (!$btc) {
                        $message .= "BUY order for $eur in " . CRYPTO . " when 1 " . CRYPTO . " will be worth $buyprice " . CURRENCY . " and sell when it's worth $sellat " . CURRENCY . "\n";
                    } else {
                        $message .= "Holding $btc " . CRYPTO . " (" . ($buyprice * $btc) . " " . CURRENCY . " at buy), will sell when it's worth $sellat " . CURRENCY . "\n";
                    }
                }
            }
        }

        $this->output->writeln($message);
    }

    /**
     * 
     * @param type $id
     */
    public function delete($id)
    {
        $this->output->writeln("[i] Deleting transaction ID $id");

        if (!is_null($this->redis)) {
            if (!$this->redis->exists("phptrader" . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ":$id:buyprice")) {
                $this->output->writeln(" [!ERR!] Key $id does not exist in Redis!\n");
            } else {
                $keys = $this->redis->keys("phptrader" . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ":$id:*");

                foreach ($keys as $key) {
                    $this->redis->del($key);
                }
            }
        } else {
            unset($this->transactions[$id]);
        }

        $this->save();
    }

    /**
     * 
     */
    protected function migrateFromJsonToRedis()
    {
        if (file_exists(STORAGE . DS . 'transactions' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . '.json')) { //migrate to redis
            $this->output->writeln("[C] Found transactions" . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ".json and Redis is configured. Converting json to Redis.. ");

            $transactions = json_decode(file_get_contents(STORAGE . DS . 'transactions' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . '.json'), true);

            foreach ($transactions as $key => $transaction) {
                $this->redis->set('phptrader' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ':' . $key . ':btc', $transaction['btc']);
                $this->redis->set('phptrader' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ':' . $key . ':eur', $transaction['eur']);
                $this->redis->set('phptrader' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ':' . $key . ':buyprice', $transaction['buyprice']);
                $this->redis->set('phptrader' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ':' . $key . ':sellat', $transaction['sellat']);
            }

            unlink(STORAGE . DS . 'transactions' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . '.json');

            $this->output->writeln("done");
        }
    }

    /**
     * 
     */
    protected function readTransactionDataFromRedis()
    {
        $this->output->writeln("[i] Loading data from Redis.. ");

        $data = $this->redis->keys('phptrader' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ':*');

        if (is_array($data)) {
            foreach ($data as $d) {
                $a = explode(':', $d);
                $key = $a[1];
                $var = $a[2];
                $val = $this->redis->get("phptrader" . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ":$key:$var");
                $this->transactions[$key][$var] = $val;
            }
        }

        $this->output->writeln("done! Found " . count($this->transactions) . " data points.");
    }

    /**
     * 
     */
    protected function readTransactionDataFromJsonFile()
    {
        if (file_exists(STORAGE . DS . 'transactions' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . '.json')) {
            $this->transactions = json_decode(file_get_contents(STORAGE . DS . 'transactions' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . '.json'), true);
        }
    }

    /**
     * 
     */
    protected function save()
    {
        if (!is_null($this->redis)) {

            foreach ($this->transactions as $key => $transaction) {
                $this->redis->set('phptrader' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ':' . $key . ':btc', $transaction['btc']);
                $this->redis->set('phptrader' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ':' . $key . ':eur', $transaction['eur']);
                $this->redis->set('phptrader' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ':' . $key . ':buyprice', $transaction['buyprice']);
                $this->redis->set('phptrader' . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ':' . $key . ':sellat', $transaction['sellat']);
            }
        } else {
            file_put_contents(STORAGE . DS . "transactions" . (CRYPTO != 'BTC' ? '-' . CRYPTO : '') . ".json", json_encode($this->transactions));
        }
    }
}
