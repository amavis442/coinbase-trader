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
class CoinbaseDebug extends Command
{

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('coinbase:debug')
            ->addOption(
                'debug', null, InputOption::VALUE_NONE, 'Debug or not?', null
            )
            
            // the short description shown while running "php bin/console list"
            ->setDescription('sell <amount in EUR> <sell when this BTC price is reached >')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Sell 100 EUR of your BTC when 1 BTC is worth 2000 EUR: coinbase:sell 100 2000')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $debug = $input->getOption('debug');
              
        dump();
        
        $t = new Trader($debug, $output);
        
        $t->debug();
                
    }
}
