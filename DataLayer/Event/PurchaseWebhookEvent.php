<?php

declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Tagging\GTM\DataLayer\Tag\Order\OrderItems;
use Magento\Sales\Api\Data\OrderInterface;
use Tagging\GTM\Util\PriceFormatter;
use Tagging\GTM\Config\Config;
use Psr\Log\LoggerInterface;
use Tagging\GTM\Logger\Debugger;

class PurchaseWebhookEvent
{
    private $json;
    private $clientFactory;
    private $config;
    private $orderItems;
    private $priceFormatter;
    private LoggerInterface $logger;
    private Debugger $debugger;

    public function __construct(
        Json            $json,
        ClientFactory   $clientFactory,
        OrderItems      $orderItems,
        Config          $config,
        PriceFormatter  $priceFormatter,
        LoggerInterface $logger,
        Debugger $debugger
    ) {
        $this->json = $json;
        $this->clientFactory = $clientFactory;
        $this->orderItems = $orderItems;
        $this->config = $config;
        $this->priceFormatter = $priceFormatter;
        $this->logger = $logger;
        $this->debugger = $debugger;
    }

    public function purchase(OrderInterface $order)
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $marketingData = [];

        try {            
            $this->debugger->debug("InvoicePaymentObserver: Processing order " . $order->getIncrementId());

            $extensionAttributes = $order->getExtensionAttributes();
            $this->debugger->debug("InvoicePaymentObserver: Extension attributes object: " . ($extensionAttributes ? 'exists' : 'is null'));
    
            if ($extensionAttributes) {
                $marketingData = $extensionAttributes->getTrytaggingMarketing();
                $this->debugger->debug("InvoicePaymentObserver: Marketing data: " . ($marketingData ?: 'is null'), $marketingData);
            }
    
            $rawMarketingData = $order->getData('trytagging_marketing');
            $this->debugger->debug("InvoicePaymentObserver: Raw marketing data from order: " . ($rawMarketingData ?: 'is null'), $rawMarketingData);
            
            if ($rawMarketingData) {
                $marketingData = $rawMarketingData;
            }

            if ($marketingData === null) {
                $marketingData = [
                    '_error' => 'trytagging_marketing data not found for order: ' . $order->getIncrementId()
                ];
            } else {
                $marketingData = $this->json->unserialize($marketingData);
            }
        } catch (\Exception $e) {
            $marketingData = [
                '_error' => $e->getMessage()
            ];
            $this->debugger->debug($e->getMessage());
        }

        $data = [
            'marketing' => $marketingData,
            'store_domain' => $this->config->getStoreDomain(),
            'plugin_version' => $this->config->getVersion(),
            'ecommerce' => [
                'transaction_id' => $order->getIncrementId(),
                'affiliation' => $this->config->getStoreName(),
                'currency' => $order->getOrderCurrencyCode(),
                'value' => $this->priceFormatter->format((float)$order->getGrandTotal()),
                'tax' => $this->priceFormatter->format((float)$order->getTaxAmount()),
                'shipping' => $this->priceFormatter->format((float)$order->getShippingInclTax()),
                'coupon' => $order->getCouponCode(),
                'items' => $this->orderItems->setOrder($order)->get()
            ]
        ];

        $data['event'] = 'trytagging_purchase';
        $client = $this->clientFactory->create();
        $client->addHeader('Content-Type', 'application/json');
        $client->addHeader('Accept', 'application/json');

        try {
            $url = $this->config->getGoogleTagmanagerUrl();
            $client->post('https://' . $url . '/order_created', $this->json->serialize($data));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $client->getStatus() == 200;
    }
}