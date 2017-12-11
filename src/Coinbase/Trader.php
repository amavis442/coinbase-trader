<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Coinbase;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Resource\Sell;
use Coinbase\Wallet\Resource\Buy;
use Coinbase\Wallet\Value\Money;
use App\Coinbase\Account;
use App\Coinbase\Wallet;
use App\Coinbase\Transaction;

/**
 * Description of Trader
 *
 * @author patrickteunissen
 */
class Trader
{

    public $lastSellPrice;
    public $buyPrice;
    public $sellPrice;
    public $spotPrice;
    private $client;
    private $account;
    private $transaction;
    private $traderID;
    private $output;
    private $wallet;
    private $cryptoAccount;
    private $currencyAccount;
    private $paymentMethod;

    /**
     * 
     * @param type $noinit
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return type
     */
    public function __construct($noinit = false, OutputInterface $output)
    {
        $configuration = Configuration::apiKey(COINBASE_KEY, COINBASE_SECRET);
        $this->client = Client::create($configuration);
            
        //$this->client

        
        if ($noinit === true) {
            return;
        }

        $this->output = $output;

        $this->traderID = CURRENCY . ' - ' . CRYPTO;

        $this->account = new Account($this->client);
        $this->account->init();
        $this->cryptoAccount = $this->account->getCryptoAccount();
        $this->currencyAccount = $this->account->getCurrencyAccount();

        $this->paymentMethod = new PaymentMethod($this->client);
        $this->paymentMethod->init();

        $this->updatePrices();

        $this->transaction = new Transaction();
        $this->transaction->init();

        $this->transactions = $this->transaction->getAll();
    }

    /**
     * 
     */
    public function debug()
    {
        $this->output->writeln("############ DEBUG START ############");

        $this->output->writeln("[i] Listing accounts");
        $accounts = $this->account->getAccounts();
        foreach ($accounts as $account) {
            $this->output->writeln(["  [W] Wallet:\t '" . $account->getName(),
                "    [Wi] ID: " . $account->getId(),
                "    [Wi] currency: " . $account->getCurrency(),
                "    [Wi] Amount: " . $account->getBalance()->getAmount()]);
        }

        $this->output->writeln("[i] Listing Payment methods");
        $paymentMethods = $this->paymentMethod->getPaymentMethods();
        foreach ($paymentMethods as $pm) {
            $this->output->writeln(["  [PM] Wallet:\t '" . $pm->getName(),
                "    [PMi] ID: " . $pm->getId(),
                "    [PMi] currency: " . $pm->getCurrency()]);
        }

        $this->output->writeln("\n############ DEBUG END ############\n");
    }

    /**
     * 
     * @param bool $silent
     */
    public function updatePrices($silent = false)
    {
        $this->lastSellPrice = $this->sellPrice;

        $this->buyPrice = floatval($this->client->getBuyPrice(CRYPTO . '-' . CURRENCY)->getAmount());
        $this->sellPrice = floatval($this->client->getSellPrice(CRYPTO . '-' . CURRENCY)->getAmount());
        $this->spotPrice = floatval($this->client->getSpotPrice(CRYPTO . '-' . CURRENCY)->getAmount());

        if (!$this->lastSellPrice) {
            $this->lastSellPrice = $this->sellPrice;
        }

        if ($silent === false) {
            $this->output->writeln(["[i] Buy price: $this->buyPrice " . CURRENCY,
                "[i] Sell price: $this->sellPrice " . CURRENCY,
                "[i] Spot price: $this->spotPrice " . CURRENCY,
                "[i] Difference buy/sell: " . round(abs($this->buyPrice - $this->sellPrice), 2) . " " . CURRENCY]);
        }
    }

    /**
     * Buys the configured crypto for real money
     * $money is $ or €, not some other crypto
     * 
     * @param type $money
     * @return type
     */
    public function buyCryptoInMoney($money)
    {
        if (SIMULATE === false) {
            $this->output->writeln(" [B] Buying $money " . CURRENCY . ' of ' . CRYPTO);

            $buy = new Buy([
                'amount' => new Money($money, CURRENCY),
                'paymentMethodId' => $this->paymentMethod->getId()
            ]);

            //check if account has enough currency
            if ($this->account->checkBalanceOfAccount($this->currencyAccount) < $money) {
                $this->output->writeln(" [ERR] You don't have enough " . CURRENCY . " in your '" . $this->currencyAccount->getName() . "'. Cancelling buy");
                return;
            } else {
                $this->client->createAccountBuy($this->cryptoAccount, $buy);
            }
        } else {
            $this->output->writeln(" [S] Simulating buy of $money " . CURRENCY . ' in ' . CRYPTO);
        }
    }

    /**
     * 
     * @param type $amount
     * @param type $sellat
     * @param type $buyPrice
     * @param type $btc
     * @return type
     */
    public function buyBTC($amount, $sellat, $btc = false)
    {
        $eur = ($btc === true ? ($this->buyPrice * $amount) : $amount);
        $btc = ($btc === true ? $amount : ($amount / $this->buyPrice));

        if (SIMULATE === false) {
            $buy = new Buy([
                'amount' => new Money($btc, CRYPTO),
                'paymentMethodId' => $this->paymentMethod->getId()
            ]);

            //check if account has enough currency
            if ($this->account->checkBalanceOfAccount($this->currencyAccount) < $eur) {
                $this->output->writeln(" [ERR] You don't have enough " . CURRENCY . " in your '" . $this->currencyAccount->getName() . "'. Cancelling buy");
                return;
            } else {
                $this->client->createAccountBuy($this->cryptoAccount, $buy);
            }
        }

        $msg = "[B #$id] Buying $eur €\t=\t$btc " . CRYPTO;
        $id = $this->transaction->add(array('btc' => $btc, 'eur' => $eur, 'buyprice' => $this->buyPrice, 'sellat' => $sellat), $mg);

        return $id;
    }

    /**
     * 
     * @param integer $id
     */
    protected function sellBTCID(integer $id)
    {
        $data = $this->transaction->find($id);
        $this->transaction->delete($id);

        $this->output->writeln("[S #$id] Removed transaction #$id from list");
        $this->sellBTC($data['btc'], true);

        $profit = round(($data['btc'] * $this->sellPrice) - ($data['btc'] * $data['buyprice']), 2);
    }

    public function sellBTC($amount, $btc = false)
    {
        $eur = ($btc === true ? ($this->sellPrice * $amount) : $amount);
        $btc = ($btc === true ? $amount : ($amount / $this->sellPrice));

        $sell = new Sell([
            'bitcoinAmount' => $btc
            //'amount' => new Money($btc, CRYPTO)
        ]);

        $this->output->writeln("[S] Selling $eur € =\t$btc " . CRYPTO);

        if (SIMULATE === false) {
            if ($this->account->checkBalanceOfAccount($this->cryptoAccount) < $btc) {
                $this->output->writeln(" [ERR] You don't have enough " . CRYPTO . " in your '" . $this->account->getName() . "'. Cancelling sell");
                return;
            } else {
                $this->client->createAccountSell($this->cryptoAccount, $sell);
            }
        }
    }

    /**
     * 
     */
    public function mainCheck()
    {
        $this->transaction->load(); //update transactions since the data could have changed by now

        $this->output->writeln("[i] Currently watching " . count($this->transactions) . " transactions");

        //only update prices if we have active transactions to watch
        if (count($this->transactions) > 0) {
            $this->updatePrices();
        }

        if ($this->lastSellPrice != $this->sellPrice && round(abs($this->sellPrice - $this->lastSellPrice), 2) > 0) {
            echo "[" . CRYPTO . "] Price went " . ($this->sellPrice > $this->lastSellPrice ? 'up' : 'down') . " by " . round($this->sellPrice - $this->lastSellPrice, 2) . " " . CURRENCY . "\n";
        }

        foreach ($this->transactions as $id => $td) {
            $btc = $td['btc'];
            $eur = $td['eur'];
            $buyprice = $td['buyprice'];
            $sellat = $td['sellat'] + $eur;
            $newprice = $btc * $this->sellPrice;

            $diff = round(($this->sellPrice - $buyprice) * $btc, 2);

            //is this a SELL order?
            if (!$buyprice) {

                if ($this->sellPrice >= $td['sellat']) { //time to sell?
                    $btc = (1 / $this->sellPrice) * $eur;
                    $this->deleteTransaction($id);
                    $this->sellBTC($btc, true);
                } else {
                    $this->output->writeln(" [#$id] Watching SELL order for \t$eur " . CURRENCY . ". Will sell when " . CRYPTO . " price reaches " . $td['sellat'] . " " . CRYPTO);
                }
            } else {

                //is this a BUY order?
                if (!$btc) {
                    if ($this->buyPrice <= $buyprice) { //time to buy?
                        $this->transaction->delete($id);
                        $this->buyBTC($eur, ($sellat - $eur));
                    } else {
                        $this->output->writeln(" [#$id] Watching BUY order for \t$eur " . CURRENCY . ". Will buy when " . CRYPTO . " price reaches $buyprice");
                    }
                } else {
                    $message = " [#$id] Holding \t$eur " . CURRENCY . " at buy. Now worth:\t " . round($newprice, 2) . " " . CURRENCY . ". Change: " . ($diff) . " " . CURRENCY . ". Will sell at \t$sellat " . CURRENCY;
                    $this->output->writeln($message);

                    if (($this->sellPrice * $btc) >= $sellat) {
                        $this->output->writeln("  [#$id] AWWYEAH time to sell $btc " . CRYPTO . " since it hit " . ($this->sellPrice * $btc) . " " . CURRENCY . ". Bought at $eur " . CURRENCY);
                        $this->sellBTCID($id);
                    }
                }
            }
        }
    }

    /**
     * 
     */
    public function report()
    {
        ob_start();
        $this->mainCheck();
        $out = ob_get_contents();
        ob_end_clean();
        //sendToRocketchat($out, ':information_source:');
    }

    /**
     * 
     * @param type $stake
     * @param int $sellpercent
     */
    public function autotrade($stake = 10, $sellpercent = 115)
    {
        if (!$stake || !is_numeric($stake) || $stake < 1) {
            $stake = 10;
        }

        if (!$sellpercent || !is_numeric($sellpercent) || $sellpercent < 1) {
            $sellpercent = 115;
        }

        if (file_exists('autotrader.txt')) {
            $data = trim(file_get_contents('autotrader.txt'));
            $a = explode(';', $data);
            $boughtat = $a[0];
            $coins = $a[1];
            $stake = $a[2];
            $this->output->writeln("[A] Loading existing autotrader with stake of $stake " . CURRENCY . ". Holding " . $coins . ' ' . CRYPTO . " at $boughtat " . CURRENCY . " per " . CRYPTO);
        } else {
            $boughtat = $this->buyPrice;
            $coins = $stake / $boughtat;
            $this->buyCryptoInMoney($stake);
            file_put_contents('autotrader.txt', "$boughtat;$coins;$stake");
            $this->output->writeln("[A] Starting autotrader with stake of $stake " . CURRENCY . "." . ($nobuy === true ? ' NOT' : '') . " Buying " . $coins . ' ' . CRYPTO . " at $boughtat " . CURRENCY . " per " . CRYPTO);
        }

        $targetprice = round(($stake / 100) * $sellpercent);

        while (1) {
            $diff = ($this->sellPrice * $coins) - ($boughtat * $coins);
            $percentdiff = round((($this->sellPrice * $coins) / ($boughtat * $coins)) * 100, 2);

            if ($percentdiff >= 115) {
                $this->output->writeln("\n  [!] Price is now $percentdiff % of buy price. Selling $stake " . CURRENCY);
                $this->sellBTC($stake);
                //aaand here we go again
                $boughtat = $this->buyPrice;
                $coins = $stake / $boughtat;
                $this->buyCryptoInMoney($stake);
                file_put_contents('autotrader.txt', "$boughtat;$coins;$stake");
                $this->output->writeln("\n[A] Re-buying with stake of $stake " . CURRENCY . ". Buying " . $coins . ' ' . CRYPTO . " at $boughtat " . CURRENCY . " per " . CRYPTO);
            } else {
                $this->output->writeln("\r [" . date("d.m H:i") . "] Current price: " . round($this->sellPrice * $coins) . " " . CURRENCY . ". $percentdiff% of target. Will sell at " . $sellpercent . "% for $targetprice " . CURRENCY . "         ");
            }
            sleep(SLEEPTIME);

            $this->updatePrices(true);
        }
    }
}
