<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Coinbase;

use Coinbase\Wallet\Client;

/**
 * Description of Account
 *
 * @author patrickteunissen
 */
class Account
{

    private $cryptoAccount;
    private $currencyWallet;
    private $client;

    public function __contruct(\Coinbase\Wallet\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Which account to use
     */
    public function init()
    {
        $accounts = $this->getAccounts();

        foreach ($accounts as $account) {
            if ($account->getCurrency() == CRYPTO) {
                $this->cryptoAccount = $account;
                $this->output->writeln("[i] Will use '" . $account->getName() . "' as crypto wallet :)");
            } else if ($account->getCurrency() == CURRENCY) {
                $this->currencyAccount = $account;
                $this->output->writeln("[i] Will use '" . $account->getName() . "' as currency wallet :)");
            }
        }

        if (!$this->cryptoAccount) {
            $this->getPrimaryAccount();
            $this->output->writeln("[W] Didn't find your '" . CRYPTO . " Wallet' Account.. falling back to default");
        }

        if (!$this->cryptoAccount) {
            throw new Exception('No account found that can be used.');
        }
    }

    public function getAccounts()
    {
        $accounts = $this->client->getAccounts();

        return $accounts;
    }

    public function getPrimaryAccount()
    {
        $this->cryptoAccount = $this->client->getPrimaryAccount();
        
        return $this->cryptoAccount;
    }

    /**
     * Account with the crypto data
     * 
     * 
     * @return type
     */
    public function getCryptoAccount()
    {
        return $this->cryptoAccount;
    }
    
    /**
     * Account with the currency account
     * 
     * @return type
     */
    public function getCurrencyAccount()
    {
        return $this->currencyAccount;
    }
    
    /**
     * 
     * @param \Coinbase\Wallet\Resource\Account $account
     * @return type
     */
    public function checkBalanceOfAccount(\Coinbase\Wallet\Resource\Account $account)
    {
        $data = $account->getBalance();
        $amount = $data->getAmount();
        $currency = $data->getCurrency();
        
        return $amount;
    }
}
