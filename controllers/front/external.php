<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

/**
 * This Controller simulate an external payment gateway
 */
class ZarinpalExternalModuleFrontController extends ModuleFrontController
{
    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        $customer = new Customer($this->context->cart->id_customer);
        if (! Validate::isLoadedObject($customer) || !$this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $amount = $this->context->cart->getOrderTotal(true);

        $params = [
            'merchant_id' => Configuration::get(Zarinpal::ZARINPAL_MERCHANT_CODE),
			'amount' => $amount,
			'callback_url' => $this->callBackUrl(),
            'currency' => Configuration::get(Zarinpal::ZARINPAL_CURRENCY),
			'description' =>  $this->l('Payment for cart: ' . $this->context->cart->id . ' | user: ' . $customer->id),
			'metadata' => [
                // 'mobile' => null,
                'email' => $customer->email ?? '',
                'order_id' => (string) $this->context->cart->id,
            ],
        ];

		$jsonData = json_encode($params);
		$ch = curl_init('https://api.zarinpal.com/pg/v4/payment/request.json');
		curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($jsonData)
		));

		$result = curl_exec($ch);
		$err = curl_error($ch);
		$result = json_decode($result, true, JSON_PRETTY_PRINT);
		curl_close($ch);


        if (! $err) {
            if ($result['data']['code'] && $result['data']['code'] === 100) {

                header("Location: https://www.zarinpal.com/pg/StartPay/" . $result['data']["authority"]);
    
            } elseif ($result['errors']) {
                $error_code = $result['errors']['code'];
                $error_message = Zarinpal::error_message($error_code);
    
                $this->errors[] = "کد خطا : " . $error_code . " <br> " . $error_message;
            }
        } else {
            $this->errors[] = "خطا در اتصال به درگاه پرداخت : <br> $err";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'action' => $this->context->link->getPageLink('order'),
        ]);

        $this->setTemplate('module:zarinpal/views/templates/front/external.tpl');
    }

    /**
     * Check that this payment option is still available in case the customer changed
     * his address just before the end of the checkout process
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        if (!Configuration::get(Zarinpal::ZARINPAL_EXTERNAL_ENABLED)) {
            return false;
        }

        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }

    public function callBackUrl()
    {
        return (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__ . 'module/zarinpal/verify';    
    }
}
