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

use App\Coinbase\Transaction;

/**
 * Description of Coinbase
 *
 * @author patrickteunissen
 */
class CoinbaseTransactionList extends Command
{

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('coinbase:transaction:list')
            ->addOption(
                'debug', null, InputOption::VALUE_NONE, 'Debug or not?', null
            )
            
            // the short description shown while running "php bin/console list"
            ->setDescription('List all transactions with IDs')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to connect to a wallet...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $t = new Transaction($output);
        
        $t->listTransactions();
                
    }
}
