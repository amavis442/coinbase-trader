<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Coinbase\Wallet\Client as CoinbaseClient;
use Coinbase\Wallet\Configuration as CoinbaseConfiguration;

/**
 * Description of Coinbase
 *
 * @author patrickteunissen
 */
class CoinbaseAccount extends Command
{

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('coinbase:account')
            ->addArgument('wallet', InputArgument::OPTIONAL, 'BTC, LTC, ETH, EUR or all?', 'LTC')

            // the short description shown while running "php bin/console list"
            ->setDescription('Show content of wallet(s).')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Example: coinbase:account EUR')
        ;
    }

    protected function showInfo(\Coinbase\Wallet\Client $client, \Coinbase\Wallet\Resource\Account $account,OutputInterface $output)
    {
        $name = $account->getName();
        $ballance = $account->getBalance();
        $amount = $ballance->getAmount();
        $currency = $ballance->getCurrency();
          
        //$transactions = $client->getAccountTransactions($account);
  
  
        $output->writeln(["[i] Wallet $name: ",
            "[i] Amount: $amount ",
            "[i] Currency: $currency "]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $wallet = $input->getArgument('wallet');

        $configuration = CoinbaseConfiguration::apiKey(COINBASE_KEY, COINBASE_SECRET);
        $client = CoinbaseClient::create($configuration);

        $accounts = $client->getAccounts();

        foreach ($accounts as &$account) {
            if ($wallet == 'all') {
                $this->showInfo($client, $account, $output);
            } else {
                //if ($account->getCurrency() == $wallet) {
                    $this->showInfo($client, $account, $output);
                //}
            }
        }
    }
}
