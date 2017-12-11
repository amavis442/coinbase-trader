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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use App\Coinbase\Trader;

/**
 * Description of Coinbase
 *
 * @author patrickteunissen
 */
class CoinbaseBuy extends Command
{

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('coinbase:buy')
            
            
            ->addArgument('amount', InputArgument::REQUIRED, 'Amount?')
            ->addArgument('sellat', InputArgument::REQUIRED, 'Sell at?')
            
            ->addOption(
                'debug', null, InputOption::VALUE_NONE, 'Debug or not?', null
            )
            
            // the short description shown while running "php bin/console list"
            ->setDescription('buy <amount in EUR> <sell when price increases by EUR>')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Buy 10 EUR in BTC and sell when it will be worth 12 EUR: coinbase:buy 10 2\n')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $debug = $input->getOption('debug');
        $amount = $input->getArgument('amount');
        $sellat = $input->getArgument('sellat');
        
        $t = new Trader($debug, $output);
        
        $t->buyBTC($amount, $sellat);
                
    }
}
