<?php

	/*
	* 2013 Presta-Shop.ir
	*
	*
	*  @author Presta-Shop.ir - Danoosh Miralayi
	*  @copyright  2013 Presta-Shop.ir
	*/

	class BankMelliValidationModuleFrontController extends ModuleFrontController {
		private
				$_payment_verify = 'https://sadad.shaparak.ir/VPG/api/v0/Advice/Verify';

		public
				$Token,
				$OrderId,
				$ResCode;

		public function __construct() {
			//$this->auth = true;
			parent::__construct();

			$this->context = Context::getContext();
		}

		public function postProcess() {
			$displayErrors = Configuration::get('Bank_Melli_phpDisplayErrors');
			if ($displayErrors) {
				@ini_set('display_errors', 'on');
			}

			$this->Token = Tools::getValue('token');
			$this->OrderId = Tools::getValue('OrderId');
			$this->ResCode = Tools::getValue('ResCode');

		}

		/**
		 * @see FrontController::initContent()
		 */
		public function initContent() {
			parent::initContent();


			if (!empty($this->ResCode) && $this->ResCode != "0") {
				$this->errors = $this->module->l('نتیجه تراکنش ناموفق است.');
			} elseif (empty($this->OrderId) || empty($this->Token)) {
				$this->errors[] = $this->module->l('اطلاعات پرداخت صحیح نیست.');
			} elseif (empty($this->context->cart->id)) {
				$this->errors[] = $this->module->l('سبد خرید شما خالی است.');
			}

			$this->validate = $this->validate();
			if (!count($this->errors) && $this->validate) {
				$OrderAmount = $this->context->cart->getOrderTotal(true, 3);
				$amount = (int)$this->context->cookie->__get('amount');
				$purchase_currency = new Currency(Currency::getIdByIsoCode('IRR'));

				if ($this->context->currency->id != $purchase_currency->id)
					$amount = number_format($this->module->convertPriceFull($amount, $purchase_currency, $this->context->currency), 0, '', '');

				$message = $this->module->l('شناسه تراکنش:') . ' ' . $this->_Melli_TraceNo . ' ' . $this->module->l('کد مرجع بانک:') . ' ' . $this->_Melli_RetrivalRefNo;

				$this->paid = $this->module->validateOrder(
						(int)$this->context->cart->id, _PS_OS_PAYMENT_,
						(float)$OrderAmount, $this->module->displayName,
						$message, array(), (int)$this->context->currency->id,
						false, $this->context->customer->secure_key
				);

				if (!$this->paid) {
					$this->errors[] = $this->module->l('خطایی در ثبت سفارش روی داد.');
				}

				$this->context->cookie->__unset("RefId");
				$this->context->cookie->__unset("amount");
			}
			$this->assignTpl();
		}

		public function validate() {
			//verify payment
			$parameters = array(
					'Token' => $this->Token,
					'SignData' => $this->module->sadad_encrypt($this->Token, Configuration::get('Bank_Melli_TerminalKey')),
			);

			$error_flag = false;
			$error_msg = '';

			$result = $this->module->sadad_call_api($this->_payment_verify, $parameters);
			file_put_contents('mylog.txt', 'sadad_call_api($this->_payment_verify', FILE_APPEND);
			file_put_contents('mylog.txt', print_r($result, true), FILE_APPEND);

			if ($result != false) {
				if ($result->ResCode == 0) {
					//payment success
					$this->_Melli_Amount = $result->Amount;
					$this->_Melli_Description = $result->Description;
					$this->_Melli_RetrivalRefNo = $result->RetrivalRefNo;
					$this->_Melli_TraceNo = $result->SystemTraceNo;
					$this->_Melli_OrderId = $result->OrderId;
					return true;
				} else {
					//couldn't verify the payment due to a back error
					$error_flag = true;
					$error_msg = $this->module->sadad_verify_err_msg($result->ResCode);
				}
			} else {
				//couldn't verify the payment due to a connection failure to bank
				$error_flag = true;
				$error_msg = 'خطا! عدم امکان دریافت تاییدیه پرداخت از بانک';
			}
			if ($error_flag) {
				$this->errors[] = $this->l($error_msg);
				$this->_postErrors[] = $this->l($error_msg);
				return false;
			}

		}


		public function assignTpl() {
			if (!isset($this->validate))
				$this->context->smarty->assign(array(
						'access' => 'denied',
						'ver' => $this->module->version
				));
			else
				$this->context->smarty->assign(array(
						'sale_order_id' => $this->_Melli_TraceNo,
						'sale_refference_id' => $this->_Melli_RetrivalRefNo,
						'paid' => isset($this->paid) ? $this->paid : false,
						'order_reference' => $this->module->currentOrderReference,
						'errors' => $this->errors,
						'ver' => $this->module->version
				));
			return $this->setTemplate('validation.tpl');
		}


	}