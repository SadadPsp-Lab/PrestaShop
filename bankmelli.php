<?php

	class BankMelli extends PaymentModule {
		private $_html = '';
		private $_postErrors = array();
		private $_payment_request = 'https://sadad.shaparak.ir/VPG/api/v0/Request/PaymentRequest';
		private $_payment_url = 'https://sadad.shaparak.ir/VPG/Purchase?Token=';

		public function __construct() {
			$this->name = 'bankmelli';
			$this->tab = 'payments_gateways';
			$this->version = '1.1';
			$this->author = 'AlmaaTech';

			$this->currencies = true;
			$this->currencies_mode = 'checkbox';

			parent::__construct();
			$this->context = Context::getContext();
			$this->page = basename(__FILE__, '.php');
			$this->displayName = $this->l('Melli Payment');
			$this->description = $this->l('A free module to pay online for Melli.');
			$this->confirmUninstall = $this->l('Are you sure, you want to delete your details?');

			if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
				$this->warning = $this->l('No currency has been set for this module');

			$config = Configuration::getMultiple(array('Bank_Melli_TerminalId', ''));
			if (!isset($config['Bank_Melli_TerminalId']))
				$this->warning = $this->l('Your Melli TerminalId must be configured in order to use this module');
			$config = Configuration::getMultiple(array('Bank_Melli_MerchantId', ''));
			if (!isset($config['Bank_Melli_MerchantId']))
				$this->warning = $this->l('Your Melli MerchantId must be configured in order to use this module');

			$config = Configuration::getMultiple(array('Bank_Melli_TerminalKey', ''));
			if (!isset($config['Bank_Melli_TerminalKey']))
				$this->warning = $this->l('Your Melli TerminalKey must be configured in order to use this module');

			if ($_SERVER['SERVER_NAME'] == 'localhost')
				$this->warning = $this->l('Your are in localhost, Melli Payment can\'t validate order');

		}

		public function install() {
			if (!parent::install()
					OR !Configuration::updateValue('Bank_Melli_TerminalId', '')
					OR !Configuration::updateValue('Bank_Melli_MerchantId', '')
					OR !Configuration::updateValue('Bank_Melli_TerminalKey', '')
					OR !Configuration::updateValue('Bank_Melli_phpDisplayErrors', 0)
					OR !$this->registerHook('payment')
					OR !$this->registerHook('paymentReturn')
			) {
				return false;
			} else {
				return true;
			}
		}

		public function uninstall() {
			if (!Configuration::deleteByName('Bank_Melli_TerminalId')
					OR !Configuration::deleteByName('Bank_Melli_MerchantId')
					OR !Configuration::deleteByName('Bank_Melli_TerminalKey')
					OR !Configuration::deleteByName('Bank_Melli_phpDisplayErrors')
					OR !parent::uninstall()
			)
				return false;
			return true;
		}

		public function displayFormSettings() {
			$this->_html .= '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
			<fieldset>
				<legend><img src="../img/admin/cog.gif" alt="" class="middle" />' . $this->l('Settings') . '</legend>
				<label>' . $this->l('terminalId') . '</label>
				<div class="margin-form"><input type="text" size="30" name="terminalId" value="' . Configuration::get('Bank_Melli_TerminalId') . '" /></div>
				<label>' . $this->l('merchantId') . '</label>
				<div class="margin-form"><input type="text" size="30" name="merchantId" value="' . Configuration::get('Bank_Melli_MerchantId') . '" /></div>
				<label>' . $this->l('terminalKey') . '</label>
				<div class="margin-form"><input type="text" size="30" name="terminalKey" value="' . Configuration::get('Bank_Melli_TerminalKey') . '" /></div>

				<label>' . $this->l('خطایابی PHP') . '</label>
				<div class="margin-form"><input type="radio" value="1" name="phpDisplayErrors" ' . (Configuration::get('Bank_Melli_phpDisplayErrors') == '1' ? "checked" : "") . ' /> <span>' . $this->l('Yes') . '</span>
				<input type="radio" value="0" name="phpDisplayErrors" ' . (Configuration::get('Bank_Melli_phpDisplayErrors') == '0' ? "checked" : "") . ' /> <span>' . $this->l('No') . '</span><span class="hint" name="help_box">جهت کشف خطاهای سرور و یا پرستاشاپ مناسب است. فقط در صورتی که در اتصال به بانک مشکل دارید فعال کنید. فراموش نکنید بعد از رفع مشکل آن را غیرفعال کنید.</span></div>
				<center><input type="submit" name="submitMelli" value="' . $this->l('Update Settings') . '" class="button" /></center>			
			</fieldset>
		</form>';
		}

		public function displayConf() {

			$this->_html .= '<div class="conf confirm"> ' . $this->l('Settings updated') . '</div>';
		}

		public function displayErrors() {
			foreach ($this->_postErrors AS $err) {
				$this->_html .= '<div class="alert error">' . $err . '</div>';
			}
		}

		public function getContent() {
			$this->_html = '<h2>' . $this->l('Melli Payment') . '</h2>';
			if (isset($_POST['submitMelli'])) {
				if (empty($_POST['terminalId']))
					$this->_postErrors[] = $this->l('Melli TerminalId is required.');

				if (empty($_POST['merchantId']))
					$this->_postErrors[] = $this->l('Your MerchantId is required.');

				if (empty($_POST['terminalKey']))
					$this->_postErrors[] = $this->l('Your TerminalKey is required.');

				if (!sizeof($this->_postErrors)) {

					Configuration::updateValue('Bank_Melli_TerminalId', $_POST['terminalId']);
					Configuration::updateValue('Bank_Melli_MerchantId', $_POST['merchantId']);
					Configuration::updateValue('Bank_Melli_TerminalKey', $_POST['terminalKey']);
					Configuration::updateValue('Bank_Melli_phpDisplayErrors', $_POST['phpDisplayErrors']);
					$this->displayConf();
				} else {
					$this->displayErrors();
				}
			}
			$this->displayFormSettings();
			return $this->_html;
		}

		public function prePayment() {
			$purchase_currency = new Currency(Currency::getIdByIsoCode('IRR'));
			$current_currency = new Currency($this->context->cookie->id_currency);
			if ($current_currency->id == $purchase_currency->id)
				$PurchaseAmount = number_format($this->context->cart->getOrderTotal(true, 3), 0, '', '');
			else
				$PurchaseAmount = number_format($this->convertPriceFull($this->context->cart->getOrderTotal(true, 3), $current_currency, $purchase_currency), 0, '', '');


			$order_id = ($this->context->cart->id) . date('YmdHis');
			$terminal_id = Configuration::get('Bank_Melli_TerminalId');
			$merchant_id = Configuration::get('Bank_Melli_MerchantId');
			$terminal_key = Configuration::get('Bank_Melli_TerminalKey');
			$amount = (int)$PurchaseAmount;

			$sign_data = $this->sadad_encrypt($terminal_id . ';' . $order_id . ';' . $amount, $terminal_key);

			$parameters = array(
					'MerchantID' => $merchant_id,
					'TerminalId' => $terminal_id,
					'Amount' => $amount,
					'OrderId' => $order_id,
					'LocalDateTime' => date('Ymdhis'),
					'ReturnUrl' => $this->context->link->getModuleLink('bankmelli', 'validation'),
					'SignData' => $sign_data,
			);

			$error_flag = false;
			$error_msg = '';

			$result = $this->sadad_call_api($this->_payment_request, $parameters);

			if ($result != false) {
				if ($result->ResCode == 0) {
					$this->context->smarty->assign(array(
							'redirect_link' => $this->_payment_url . $result->Token,
							'Token' => $result->Token
					));
					return true;
				} else {
					//bank returned an error
					$error_flag = true;
					$error_msg = $result->Description;
				}
			} else {
				// couldn't connect to bank
				$error_flag = true;
				$error_msg = 'خطا! برقراری ارتباط با بانک امکان پذیر نیست.';
			}

			if ($error_flag) {
				$this->_postErrors[] = $this->l($error_msg);
				$this->displayErrors();
				return false;
			}

		}


		public function hookPayment($params) {
			if (!$this->active)
				return;
			return $this->display(__FILE__, 'payment.tpl');
		}

		public function hookPaymentReturn($params) {
			return;
		}

		/**
		 *
		 * @return float converted amount from a currency to an other currency
		 * @param float $amount
		 * @param Currency $currency_from if null we used the default currency
		 * @param Currency $currency_to if null we used the default currency
		 */
		public static function convertPriceFull($amount, Currency $currency_from = null, Currency $currency_to = null) {
			if ($currency_from === $currency_to)
				return $amount;
			if ($currency_from === null)
				$currency_from = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
			if ($currency_to === null)
				$currency_to = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
			if ($currency_from->id == Configuration::get('PS_CURRENCY_DEFAULT'))
				$amount *= $currency_to->conversion_rate;
			else {
				$conversion_rate = ($currency_from->conversion_rate == 0 ? 1 : $currency_from->conversion_rate);
				// Convert amount to default currency (using the old currency rate)
				$amount = Tools::ps_round($amount / $conversion_rate, 2);
				// Convert to new currency
				$amount *= $currency_to->conversion_rate;
			}
			return Tools::ps_round($amount, 2);
		}
		
		
		//Create sign data(Tripledes(ECB,PKCS7)) using mcrypt
		private function mcrypt_encrypt_pkcs7($str, $key) {
			$block = mcrypt_get_block_size("tripledes", "ecb");
			$pad = $block - (strlen($str) % $block);
			$str .= str_repeat(chr($pad), $pad);
			$ciphertext = mcrypt_encrypt("tripledes", $key, $str,"ecb");
			return base64_encode($ciphertext);
		}

		//Create sign data(Tripledes(ECB,PKCS7)) using openssl
		private function openssl_encrypt_pkcs7($key, $data) {
			$ivlen = openssl_cipher_iv_length('des-ede3');
			$iv = openssl_random_pseudo_bytes($ivlen);
			$encData = openssl_encrypt($data, 'des-ede3', $key, 0, $iv);
			return $encData;
		}


		public function sadad_encrypt($data, $key) {
            $key = base64_decode($key);
            if( function_exists('openssl_encrypt') ) {
                return $this->openssl_encrypt_pkcs7($key, $data);
            } elseif( function_exists('mcrypt_encrypt') ) {
                return $this->mcrypt_encrypt_pkcs7($data, $key);
            } /*else {
                require_once 'TripleDES.php';
                $cipher = new Crypt_TripleDES();
                return $cipher->letsEncrypt($key, $data);
            }*/
		}

		public function sadad_call_api($url, $data = false) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json; charset=utf-8'));
			curl_setopt($ch, CURLOPT_POST, 1);
			if ($data) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$result = curl_exec($ch);
			curl_close($ch);
			return !empty($result) ? json_decode($result) : false;
		}

		public function sadad_request_err_msg($err_code) {

			switch ($err_code) {
				case 3:
					$message = 'پذيرنده کارت فعال نیست لطفا با بخش امورپذيرندگان, تماس حاصل فرمائید.';
					break;
				case 23:
					$message = 'پذيرنده کارت نامعتبر است لطفا با بخش امورذيرندگان, تماس حاصل فرمائید.';
					break;
				case 58:
					$message = 'انجام تراکنش مربوطه توسط پايانه ی انجام دهنده مجاز نمی باشد.';
					break;
				case 61:
					$message = 'مبلغ تراکنش از حد مجاز بالاتر است.';
					break;
				case 1000:
					$message = 'ترتیب پارامترهای ارسالی اشتباه می باشد, لطفا مسئول فنی پذيرنده با بانکماس حاصل فرمايند.';
					break;
				case 1001:
					$message = 'لطفا مسئول فنی پذيرنده با بانک تماس حاصل فرمايند,پارامترهای پرداختاشتباه می باشد.';
					break;
				case 1002:
					$message = 'خطا در سیستم- تراکنش ناموفق';
					break;
				case 1003:
					$message = 'آی پی پذیرنده اشتباه است. لطفا مسئول فنی پذیرنده با بانک تماس حاصل فرمایند.';
					break;
				case 1004:
					$message = 'لطفا مسئول فنی پذيرنده با بانک تماس حاصل فرمايند,شماره پذيرندهاشتباه است.';
					break;
				case 1005:
					$message = 'خطای دسترسی:لطفا بعدا تلاش فرمايید.';
					break;
				case 1006:
					$message = 'خطا در سیستم';
					break;
				case 1011:
					$message = 'درخواست تکراری- شماره سفارش تکراری می باشد.';
					break;
				case 1012:
					$message = 'اطلاعات پذيرنده صحیح نیست,يکی از موارد تاريخ,زمان يا کلید تراکنش
                                اشتباه است.لطفا مسئول فنی پذيرنده با بانک تماس حاصل فرمايند.';
					break;
				case 1015:
					$message = 'پاسخ خطای نامشخص از سمت مرکز';
					break;
				case 1017:
					$message = 'مبلغ درخواستی شما جهت پرداخت از حد مجاز تعريف شده برای اين پذيرنده بیشتر است';
					break;
				case 1018:
					$message = 'اشکال در تاريخ و زمان سیستم. لطفا تاريخ و زمان سرور خود را با بانک هماهنگ نمايید';
					break;
				case 1019:
					$message = 'امکان پرداخت از طريق سیستم شتاب برای اين پذيرنده امکان پذير نیست';
					break;
				case 1020:
					$message = 'پذيرنده غیرفعال شده است.لطفا جهت فعال سازی با بانک تماس بگیريد';
					break;
				case 1023:
					$message = 'آدرس بازگشت پذيرنده نامعتبر است';
					break;
				case 1024:
					$message = 'مهر زمانی پذيرنده نامعتبر است';
					break;
				case 1025:
					$message = 'امضا تراکنش نامعتبر است';
					break;
				case 1026:
					$message = 'شماره سفارش تراکنش نامعتبر است';
					break;
				case 1027:
					$message = 'شماره پذيرنده نامعتبر است';
					break;
				case 1028:
					$message = 'شماره ترمینال پذيرنده نامعتبر است';
					break;
				case 1029:
					$message = 'آدرس IP پرداخت در محدوده آدرس های معتبر اعلام شده توسط پذيرنده نیست .لطفا مسئول فنی پذيرنده با بانک تماس حاصل فرمايند';
					break;
				case 1030:
					$message = 'آدرس Domain پرداخت در محدوده آدرس های معتبر اعلام شده توسط پذيرنده نیست .لطفا مسئول فنی پذيرنده با بانک تماس حاصل فرمايند';
					break;
				case 1031:
					$message = 'مهلت زمانی شما جهت پرداخت به پايان رسیده است.لطفا مجددا سعی بفرمايید .';
					break;
				case 1032:
					$message = 'پرداخت با اين کارت . برای پذيرنده مورد نظر شما امکان پذير نیست.لطفا از کارتهای مجاز که توسط پذيرنده معرفی شده است . استفاده نمايید.';
					break;
				case 1033:
					$message = 'به علت مشکل در سايت پذيرنده. پرداخت برای اين پذيرنده غیرفعال شده
                                است.لطفا مسوول فنی سايت پذيرنده با بانک تماس حاصل فرمايند.';
					break;
				case 1036:
					$message = 'اطلاعات اضافی ارسال نشده يا دارای اشکال است';
					break;
				case 1037:
					$message = 'شماره پذيرنده يا شماره ترمینال پذيرنده صحیح نمیباشد';
					break;
				case 1053:
					$message = 'خطا: درخواست معتبر, از سمت پذيرنده صورت نگرفته است لطفا اطلاعات پذيرنده خود را چک کنید.';
					break;
				case 1055:
					$message = 'مقدار غیرمجاز در ورود اطلاعات';
					break;
				case 1056:
					$message = 'سیستم موقتا قطع میباشد.لطفا بعدا تلاش فرمايید.';
					break;
				case 1058:
					$message = 'سرويس پرداخت اينترنتی خارج از سرويس می باشد.لطفا بعدا سعی بفرمايید.';
					break;
				case 1061:
					$message = 'اشکال در تولید کد يکتا. لطفا مرورگر خود را بسته و با اجرای مجدد مرورگر « عملیات پرداخت را انجام دهید )احتمال استفاده از دکمه Back » مرورگر(';
					break;
				case 1064:
					$message = 'لطفا مجددا سعی بفرمايید';
					break;
				case 1065:
					$message = 'ارتباط ناموفق .لطفا چند لحظه ديگر مجددا سعی کنید';
					break;
				case 1066:
					$message = 'سیستم سرويس دهی پرداخت موقتا غیر فعال شده است';
					break;
				case 1068:
					$message = 'با عرض پوزش به علت بروزرسانی . سیستم موقتا قطع میباشد.';
					break;
				case 1072:
					$message = 'خطا در پردازش پارامترهای اختیاری پذيرنده';
					break;
				case 1101:
					$message = 'مبلغ تراکنش نامعتبر است';
					break;
				case 1103:
					$message = 'توکن ارسالی نامعتبر است';
					break;
				case 1104:
					$message = 'اطلاعات تسهیم صحیح نیست';
					break;
				default:
					$message = 'خطای نامشخص';
			}
			return $this->l($message);
		}

		public function sadad_verify_err_msg($res_code) {
			$error_text = '';
			switch ($res_code) {
				case -1:
				case '-1':
					$error_text = 'پارامترهای ارسالی صحیح نیست و يا تراکنش در سیستم وجود ندارد.';
					break;
				case 101:
				case '101':
					$error_text = 'مهلت ارسال تراکنش به پايان رسیده است.';
					break;
			}
			return $this->l($error_text);
		}



	}