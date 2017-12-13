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
class CoinbasePrice extends Command
{

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('coinbase:price')
            ->addArgument('crypto', InputArgument::OPTIONAL, 'BTC, LTC or ETH?','LTC')
            ->addArgument('currency', InputArgument::OPTIONAL, 'EUR, USD?','EUR')


            // the short description shown while running "php bin/console list"
            ->setDescription('Show the prices.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Example: coinbase:price LTC EUR')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $crypto = $input->getArgument('crypto');
        $currency = $input->getArgument('currency');

        $configuration = CoinbaseConfiguration::apiKey(COINBASE_KEY, COINBASE_SECRET);
        $client = CoinbaseClient::create($configuration);

        $buyPrice = floatval($client->getBuyPrice($crypto . '-' . $currency)->getAmount());
        $sellPrice = floatval($client->getSellPrice($crypto . '-' . $currency)->getAmount());
        $spotPrice = floatval($client->getSpotPrice($crypto . '-' . $currency)->getAmount());

        $output->writeln(["[i] Buy price $crypto: $buyPrice " . $currency,
            "[i] Sell price: $sellPrice " . $currency,
            "[i] Spot price: $spotPrice " . $currency,
            "[i] Difference buy/sell: " . round(abs($buyPrice - $sellPrice), 2) . " " . $currency]);
    }
}
