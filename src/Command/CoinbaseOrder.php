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
class CoinbaseOrder extends Command
{

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('coinbase:order')
            
            
            ->addArgument('amount', InputArgument::REQUIRED, 'Amount EUR?')
            ->addArgument('sellat', InputArgument::REQUIRED, 'Sell when price increases by x EUR?')
            ->addArgument('buyat', InputArgument::REQUIRED, 'Buy BTC at?')
            
            ->addOption(
                'debug', null, InputOption::VALUE_NONE, 'Debug or not?', null
            )
            
            // the short description shown while running "php bin/console list"
            ->setDescription('order <amount in EUR> <sell when price increases by EUR> <buy at BTC price>')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Add buy order for 15 EUR when 1 BTC is worth 1000 EUR and sell when the 15 EUR are worth 17 EUR: coinbase:order 15 2 1000')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $debug = $input->getOption('debug');
        $amount = $input->getArgument('amount');
        $buyat = $input->getArgument('buyat');
        $sellat = $input->getArgument('sellat');
        
        $t = new Trader($debug,  $output);
        
        $t->addBuyTransaction($amount,$buyat,$sellat);
                
    }
}
