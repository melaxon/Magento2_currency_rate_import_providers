<?php
/**
 * @author Kozeta Team
 * @copyright Copyright (c) 2019 Kozeta (https://www.kozeta.lt)
 * @package Kozeta_Curency
 */

namespace Kozeta\Currency\Model\Currency\Import;

use Kozeta\Currency\Model\Currency\Datafeed;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Currency rate import from https://www.coinpayments.net/
 */
class Coingate extends \Magento\Directory\Model\Currency\Import\AbstractImport
{
    /**
     * @var string
     */
    const CG_RATE_URL = 'https://api.coingate.com/v2/rates/merchant/{{CURRENCY_FROM}}/{{CURRENCY_TO}}';
//    const CG_API_URL = 'https://api.coingate.com/v2/rates';
//    const CG_PING = 'https://api.coingate.com/v2/ping';
    
    /**
     * @var Curl
     */
    private $_curl;

    /**
     * Core scope config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Datafeed
     *
     * @var Datafeed
     */
    private $dataFeed;

    /**
     * Initialize dependencies
     *
     * @param CurrencyFactory $currencyFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     */
    public function __construct(
        CurrencyFactory $currencyFactory,
        ScopeConfigInterface $scopeConfig,
        Datafeed $dataFeed,
        Curl $curl
    ) {
        parent::__construct($currencyFactory);
        $this->scopeConfig = $scopeConfig;
        $this->dataFeed = $dataFeed;
        $this->_curl = $curl;
    }

    /**
     * Calculate currency rates through BTC
     *
     * @param $feed
     * @param $currencyFrom
     * @param $currencyTo
     * return (float) $rate or false
     */
    private function calculateRate($feed, $currencyFrom, $currencyTo) {
        $pattern = '/^[-+]?(((\d+)\.?(\d+)?)|\.\d+)([eE]?[+-]?\d+)?$/';
        $rate = $feed['coingate'][$currencyTo][$currencyFrom];
        if (!is_bool($rate) && (is_float($rate) || preg_match($pattern, trim($rate)))) {
            return $rate;
        }

        $this->_messages[] = __("$rate - Cannot get %1 / %2 rate.", $currencyFrom, $currencyTo);
        return false;
    }

    /**
     * @param string $currencyFrom
     * @param string $currencyTo
     * @param int $retry
     * @return float|null
     */
    protected function _convert($currencyFrom, $currencyTo, $retry = 0, $short = 1)
    {
      
        //get saved datafeed
        $feed = $this->dataFeed->getDatafeed();
        if (!empty($feed && isset($feed['coingate'][$currencyTo][$currencyFrom]))) {
            return $this->calculateRate($feed, $currencyFrom, $currencyTo);
        }
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        
        $timeout = (int)$this->scopeConfig->getValue('currency/coinpayments/timeout', $scope);
        $url = self::CG_RATE_URL;
        $url = str_replace('{{CURRENCY_FROM}}', $currencyFrom, $url);
        $url = str_replace('{{CURRENCY_TO}}', $currencyTo, $url);

        try {
            
            //$this->_curl->addHeader('HMAC', hash_hmac('sha512', http_build_query($data), $privateKey));
            $this->_curl->addHeader('Content-Type', 'text/plain');
            $this->_curl->get($url, []);
            $feed['coingate'][$currencyTo][$currencyFrom] = $this->_curl->getBody();
            //$response = json_decode($this->_curl->getBody());
            

            $this->dataFeed->setDatafeed($feed);

            return $this->calculateRate($feed, $currencyFrom, $currencyTo);

        } catch (\Exception $e) {
            if ($retry == 0) {
                $this->_convert($currencyFrom, $currencyTo, 1);
            } else {
                $this->_messages[] = __('We can\'t retrieve the rates from url %1.', $url);
            }
            throw $e;
        }
        return false;
    }
}
