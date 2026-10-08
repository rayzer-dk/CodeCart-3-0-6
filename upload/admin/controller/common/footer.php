<?php
class ControllerCommonFooter extends Controller {
	public function index() {
		$this->load->language('common/footer');

		foreach (array(
			'text_footer','text_project_support','text_support','text_report','text_report_title','text_report_intro','text_report_mail','text_report_gmail','text_report_outlook','text_report_copy','project_title','project_subtitle','project_intro','project_free_title','project_free_text',
			'project_compat_title','project_compat_text','project_quality_title','project_quality_text','project_help_title','project_help_text',
			'project_payment_title','project_payment_note','project_amount_any','project_temp','project_copy','project_qr','project_open','project_copied','project_crypto_title','project_crypto_note','project_resources_title',
			'project_support_title','project_support_text','project_close','text_community','text_repository'
		) as $key) {
			$data[$key] = $this->language->get($key);
		}

		$logged = $this->user->isLogged() && isset($this->request->get['user_token']) && ($this->request->get['user_token'] == $this->session->data['user_token']);

		if ($logged) {
			$data['text_codecart_release'] = 'CodeCart PRO ' . VERSION;
		} else {
			$data['text_codecart_release'] = '';
		}

		$data['project_support_enabled'] = $logged;
		$data['report_email'] = \CodeCart\Core\Community::EMAIL;
		$data['community_url'] = \CodeCart\Core\Community::TELEGRAM;
		$data['repository_url'] = \CodeCart\Core\Community::REPOSITORY;
		$report_subject = $this->language->get('text_report_subject');
		$report_body = $this->language->get('text_report_body');
		$data['report_mailto_url'] = 'mailto:' . $data['report_email'] . '?subject=' . rawurlencode($report_subject) . '&body=' . rawurlencode($report_body);
		$data['report_gmail_url'] = 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode($data['report_email']) . '&su=' . rawurlencode($report_subject) . '&body=' . rawurlencode($report_body);
		$data['report_outlook_url'] = 'https://outlook.office.com/mail/deeplink/compose?to=' . rawurlencode($data['report_email']) . '&subject=' . rawurlencode($report_subject) . '&body=' . rawurlencode($report_body);

		$data['project_support_methods'] = array(
			array(
				'icon' => 'fa-credit-card', 'name' => 'Monobank', 'group' => 'main',
				'value' => 'send.monobank.ua/jar/7eE1YTYqS3',
				'copy' => 'https://send.monobank.ua/jar/7eE1YTYqS3',
				'url' => 'https://send.monobank.ua/jar/7eE1YTYqS3',
				'qr_value' => 'https://send.monobank.ua/jar/7eE1YTYqS3',
				'qr' => 'view/image/support/monobank.png'
			),
			array(
				'icon' => 'fa-university', 'name' => 'Revolut', 'group' => 'main',
				'value' => 'revolut.me/codecartpro',
				'copy' => 'https://revolut.me/codecartpro',
				'url' => 'https://revolut.me/codecartpro',
				'qr_value' => 'https://revolut.me/codecartpro',
				'qr' => 'view/image/support/revolut.png'
			),
			array(
				'icon' => 'fa-credit-card', 'name' => 'Google Pay / Apple Pay', 'group' => 'main',
				'value' => 'https://buy.stripe.com/6oU00j3nk0YJ2j3ecEe3e02',
				'copy' => 'https://buy.stripe.com/6oU00j3nk0YJ2j3ecEe3e02',
				'url' => 'https://buy.stripe.com/6oU00j3nk0YJ2j3ecEe3e02',
				'qr_value' => 'https://buy.stripe.com/6oU00j3nk0YJ2j3ecEe3e02',
				'qr' => 'view/image/support/card.png'
			),
			array(
				'icon' => 'fa-coffee', 'name' => 'Ko-fi', 'group' => 'main',
				'value' => 'ko-fi.com/codecartpro',
				'copy' => 'https://ko-fi.com/codecartpro',
				'url' => 'https://ko-fi.com/codecartpro',
				'qr_value' => 'https://ko-fi.com/codecartpro',
				'qr' => 'view/image/support/kofi.png'
			),
			array(
				'icon' => 'fa-qrcode', 'name' => 'TRON (TRX)', 'group' => 'crypto',
				'value' => 'TU7AJ2LAk56AwU5hwVoS2mYM7j1YtJkbGT',
				'copy' => 'TU7AJ2LAk56AwU5hwVoS2mYM7j1YtJkbGT',
				'url' => 'tron:TU7AJ2LAk56AwU5hwVoS2mYM7j1YtJkbGT',
				'qr_value' => 'tron:TU7AJ2LAk56AwU5hwVoS2mYM7j1YtJkbGT',
				'qr' => 'view/image/support/tron.png'
			),
			array(
				'icon' => 'fa-qrcode', 'name' => 'Ethereum (ETH)', 'group' => 'crypto',
				'value' => '0x795595f169aaE72340aFDaB71Fe98064a9A305C8',
				'copy' => '0x795595f169aaE72340aFDaB71Fe98064a9A305C8',
				'url' => 'ethereum:0x795595f169aaE72340aFDaB71Fe98064a9A305C8',
				'qr_value' => 'ethereum:0x795595f169aaE72340aFDaB71Fe98064a9A305C8',
				'qr' => 'view/image/support/ethereum.png'
			),
			array(
				'icon' => 'fa-btc', 'name' => 'Bitcoin (BTC)', 'group' => 'crypto',
				'value' => 'bc1qs54gmdr3zz7rjk2fd5djw28leg67edds2vl5sp',
				'copy' => 'bc1qs54gmdr3zz7rjk2fd5djw28leg67edds2vl5sp',
				'url' => 'bitcoin:bc1qs54gmdr3zz7rjk2fd5djw28leg67edds2vl5sp',
				'qr_value' => 'bitcoin:bc1qs54gmdr3zz7rjk2fd5djw28leg67edds2vl5sp',
				'qr' => 'view/image/support/bitcoin.png'
			),
			array(
				'icon' => 'fa-qrcode', 'name' => 'Litecoin (LTC)', 'group' => 'crypto',
				'value' => 'ltc1qjle4q0dqwzs27sfavuk07ynj9yfs4je8542pc2',
				'copy' => 'ltc1qjle4q0dqwzs27sfavuk07ynj9yfs4je8542pc2',
				'url' => 'litecoin:ltc1qjle4q0dqwzs27sfavuk07ynj9yfs4je8542pc2',
				'qr_value' => 'litecoin:ltc1qjle4q0dqwzs27sfavuk07ynj9yfs4je8542pc2',
				'qr' => 'view/image/support/litecoin.png'
			)
		);

		$data['project_links'] = array(
			array('icon' => 'fa-comments', 'name' => $this->language->get('text_community'), 'value' => \CodeCart\Core\Community::TELEGRAM, 'url' => \CodeCart\Core\Community::TELEGRAM),
			array('icon' => 'fa-github', 'name' => $this->language->get('text_repository'), 'value' => \CodeCart\Core\Community::REPOSITORY, 'url' => \CodeCart\Core\Community::REPOSITORY),
			array('icon' => 'fa-heart', 'name' => $this->language->get('text_project_support'), 'value' => 'https://codecartpro.com/project-support', 'url' => 'https://codecartpro.com/project-support'),
			array('icon' => 'fa-globe', 'name' => $this->language->get('project_link_modules'), 'value' => 'https://codecartpro.com/', 'url' => 'https://codecartpro.com/'),
			array('icon' => 'fa-download', 'name' => $this->language->get('project_link_download'), 'value' => 'https://codecartpro.com/download/', 'url' => 'https://codecartpro.com/download/'),
			array('icon' => 'fa-envelope', 'name' => $this->language->get('project_link_support'), 'value' => 'support@codecartpro.com', 'url' => 'mailto:support@codecartpro.com')
		);

		$data['modern_assets'] = method_exists($this->document, 'getAssets') ? $this->document->getAssets('footer') : array();

		return $this->load->view('common/footer', $data);
	}
}
