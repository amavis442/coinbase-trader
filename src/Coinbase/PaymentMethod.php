<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Coinbase;

use Coinbase\Wallet\Client;

/**
 * Description of PaymentMethod
 *
 * @author patrickteunissen
 */
class PaymentMethod
{

    private $client;
    private $paymentMethod;
 
    public function __contruct(\Coinbase\Wallet\Client $client)
    {
        $this->client = $client;
    }
   
    /**
     * 
     * @throws RuntimeException
     */
    public function init()
    {
        $paymentMethods = $this->getPaymentMethods();
        // legacy support so users won't have to change their config
        if (!PAYMENT_METHOD_NAME) {
            putenv('PAYMENT_METHOD_NAME=' . CURRENCY . ' Wallet');
        }

        //find payment ID
        foreach ($paymentMethods as $pm) {
            if ($pm->getName() == PAYMENT_METHOD_NAME) {
                $this->paymentMethod = $pm;
                $this->output->writeln("[i] Will use " . $pm->getName() . " for payments");
                break;
            }
        }

        if (!$this->paymentMethod) {
            throw new RuntimeException("[ERR] Could not find your payment method: '" . PAYMENT_METHOD_NAME);
        }
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }
    
    public function getId()
    {
        return $this->paymentMethod->getId();
    }
    
    public function getPaymentMethods()
    {
        $paymentMethods = $this->client->getPaymentMethods();
        
        return $paymentMethods;
    }
           
}
