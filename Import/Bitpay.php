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
 * Currency rate import from https://www.bitpay.com/
 */
class Bitpay extends \Magento\Directory\Model\Currency\Import\AbstractImport
{
    /**
     * @var string
     */
    const BP_API_URL = 'https://bitpay.com/api/rates/BTC';
    
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
    
        list($currencyFrom, $currencyTo) = [$currencyTo, $currencyFrom];
        
        if ($currencyTo == 'BTC') {
            if (isset($feed[$currencyFrom])) {
                return $feed[$currencyFrom]['rate'];
            }
        }
        if ($currencyFrom == 'BTC') {
            if (isset($feed[$currencyTo])) {
                $feed[$currencyTo]['rate'] = (float) $feed[$currencyTo]['rate'];
                if ($feed[$currencyTo]['rate'] == 0) {
                    $this->_messages[] = __('The rate of currency %1 nears to zero.', $currencyTo);
                    return false;
                }
                return 1 / $feed[$currencyTo]['rate'];
            }
        }
        if (isset($feed[$currencyFrom])) {
            $feed[$currencyFrom]['rate'] = (float) $feed[$currencyFrom]['rate'];
            if ($feed[$currencyFrom]['rate'] == 0) {
                $this->_messages[] = __('The rate of currency %1 nears to zero.', $currencyFrom);
                return false;
            }
            if (isset($feed[$currencyTo])) {
                if ($feed[$currencyTo]['rate'] == 0) {
                    $this->_messages[] = __('The rate of currency %1 nears to zero.', $currencyTo);
                    return false;
                }
                $feed[$currencyTo]['rate'] = (float) $feed[$currencyTo]['rate'];
                return $feed[$currencyFrom]['rate'] / $feed[$currencyTo]['rate'];
            }
            $this->_messages[] = __('Currency %1 is not present in Bitpay datafeed.', $currencyTo);
            return false;
        }
        $this->_messages[] = __('Currency %1 is not present in Bitpay datafeed.', $currencyFrom);
        return false;
    }

    /**
     * @param string $currencyFrom
     * @param string $currencyTo
     * @param int $retry
     * @return float|null
     */
    protected function _convert($currencyFrom, $currencyTo, $retry = 0)
    {
      
        //get saved datafeed
        $feed = $this->dataFeed->getDatafeed();
        if (!empty($feed)) {
            return $this->calculateRate($feed, $currencyFrom, $currencyTo);
        }
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        
        $timeout = (int)$this->scopeConfig->getValue('currency/bitpay/timeout', $scope);
        $url = self::BP_API_URL;
        
        try {
            $this->_curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
            $this->_curl->get($url);
            $response = $this->_curl->getBody();
            $response = json_decode($this->_curl->getBody());
            
            $feed = [];
            foreach ($response as $item) {
                $feed[$item->code] = [
                    'code' => $item->code,
                    'rate' => $item->rate,
                    'name' => $item->name,
                ];
            }

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
