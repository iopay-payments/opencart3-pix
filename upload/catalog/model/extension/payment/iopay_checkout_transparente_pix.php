<?php

/**
 * IOPAY Checkout v.10
 *
 * @date 19/10/2020 12:23h GMT -3
 *
 * Class ModelExtensionPaymentIOCheckout
 */
class ModelExtensionPaymentIOPAYCheckoutTransparentePix extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/iopay_checkout_transparente_pix');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_iopay_checkout_transparente_pix_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('payment_iopay_checkout_transparente_pix_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('payment_iopay_checkout_transparente_pix_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}


        /**
         * @todo remover USD dollar in production
         */
		$currencies = array(
			'USD',
            'BRL'
		);

		if (!in_array(strtoupper($this->session->data['currency']), $currencies)) {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'iopay_checkout_transparente_pix',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_iopay_checkout_transparente_pix_sort_order')
			);
		}

		return $method_data;
	}
}
