<?php

/**
 * IOPAY Checkout v1.0
 *
 * Class ControllerExtensionPaymentIOCheckoutPix
 */
class ControllerExtensionPaymentIOPAYCheckoutTransparentePix extends Controller {
	private $error = array();

	public function index() {

        $this->load->model('customer/custom_field'); //aqui puxa o model do catalog/account/custom_field
        $custom_fields = $this->model_customer_custom_field->getCustomFields();

        $this->load->language('extension/payment/iopay_checkout_transparente_pix');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_iopay_checkout_transparente_pix', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

        $data['custom_fields'] = $custom_fields; // custom fields for mapping

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/iopay_checkout_transparente_pix', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/iopay_checkout_transparente_pix', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);


        if (isset($this->request->post['payment_iopay_checkout_transparente_pix_mapping_user_primarydocument'])) {
            $data['payment_iopay_checkout_transparente_pix_mapping_user_primarydocument'] = $this->request->post['payment_iopay_checkout_transparente_pix_mapping_user_primarydocument'];
        } else {
            $data['payment_iopay_checkout_transparente_pix_mapping_user_primarydocument'] = $this->config->get('payment_iopay_checkout_transparente_pix_mapping_user_primarydocument');
        }

        if (isset($this->request->post['payment_iopay_checkout_transparente_pix_mapping_shipping_primarydocument'])) {
            $data['payment_iopay_checkout_transparente_pix_mapping_shipping_primarydocument'] = $this->request->post['payment_iopay_checkout_transparente_pix_mapping_shipping_primarydocument'];
        } else {
            $data['payment_iopay_checkout_transparente_pix_mapping_shipping_primarydocument'] = $this->config->get('payment_iopay_checkout_transparente_pix_mapping_shipping_primarydocument');
        }

        if (isset($this->request->post['payment_iopay_checkout_transparente_pix_mapping_shipping_addressnumber'])) {
            $data['payment_iopay_checkout_transparente_pix_mapping_shipping_addressnumber'] = $this->request->post['payment_iopay_checkout_transparente_pix_mapping_shipping_addressnumber'];
        } else {
            $data['payment_iopay_checkout_transparente_pix_mapping_shipping_addressnumber'] = $this->config->get('payment_iopay_checkout_transparente_pix_mapping_shipping_addressnumber');
        }

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_email'])) {
			$data['payment_iopay_checkout_transparente_pix_email'] = $this->request->post['payment_iopay_checkout_transparente_pix_email'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_email'] = $this->config->get('payment_iopay_checkout_transparente_pix_email');
		}

        if (isset($this->request->post['payment_iopay_checkout_transparente_pix_ioid'])) {
            $data['payment_iopay_checkout_transparente_pix_ioid'] = $this->request->post['payment_iopay_checkout_transparente_pix_ioid'];
        } else {
            $data['payment_iopay_checkout_transparente_pix_ioid'] = $this->config->get('payment_iopay_checkout_transparente_pix_ioid');
        }

        if (isset($this->request->post['payment_iopay_checkout_transparente_pix_io_api_secret'])) {
            $data['payment_iopay_checkout_transparente_pix_io_api_secret'] = $this->request->post['payment_iopay_checkout_transparente_pix_io_api_secret'];
        } else {
            $data['payment_iopay_checkout_transparente_pix_io_api_secret'] = $this->config->get('payment_iopay_checkout_transparente_pix_io_api_secret');
        }

        if (isset($this->request->post['payment_iopay_checkout_transparente_pix_antifraud_type'])) {
            $data['payment_iopay_checkout_transparente_pix_antifraud_type'] = $this->request->post['payment_iopay_checkout_transparente_pix_antifraud_type'];
        } else {
            $data['payment_iopay_checkout_transparente_pix_antifraud_type'] = $this->config->get('payment_iopay_checkout_transparente_pix_antifraud_type');
        }

        if (isset($this->request->post['payment_iopay_checkout_transparente_pix_antifraud_id'])) {
            $data['payment_iopay_checkout_transparente_pix_antifraud_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_antifraud_id'];
        } else {
            $data['payment_iopay_checkout_transparente_pix_antifraud_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_antifraud_id');
        }

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_test'])) {
			$data['payment_iopay_checkout_transparente_pix_test'] = $this->request->post['payment_iopay_checkout_transparente_pix_test'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_test'] = $this->config->get('payment_iopay_checkout_transparente_pix_test');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_transaction'])) {
			$data['payment_iopay_checkout_transparente_pix_transaction'] = $this->request->post['payment_iopay_checkout_transparente_pix_transaction'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_transaction'] = $this->config->get('payment_iopay_checkout_transparente_pix_transaction');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_debug'])) {
			$data['payment_iopay_checkout_transparente_pix_debug'] = $this->request->post['payment_iopay_checkout_transparente_pix_debug'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_debug'] = $this->config->get('payment_iopay_checkout_transparente_pix_debug');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_total'])) {
			$data['payment_iopay_checkout_transparente_pix_total'] = $this->request->post['payment_iopay_checkout_transparente_pix_total'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_total'] = $this->config->get('payment_iopay_checkout_transparente_pix_total');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_canceled_reversal_status_id'])) {
			$data['payment_iopay_checkout_transparente_pix_canceled_reversal_status_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_canceled_reversal_status_id'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_canceled_reversal_status_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_canceled_reversal_status_id');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_completed_status_id'])) {
			$data['payment_iopay_checkout_transparente_pix_completed_status_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_completed_status_id'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_completed_status_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_completed_status_id');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_denied_status_id'])) {
			$data['payment_iopay_checkout_transparente_pix_denied_status_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_denied_status_id'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_denied_status_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_denied_status_id');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_expired_status_id'])) {
			$data['payment_iopay_checkout_transparente_pix_expired_status_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_expired_status_id'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_expired_status_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_expired_status_id');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_failed_status_id'])) {
			$data['payment_iopay_checkout_transparente_pix_failed_status_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_failed_status_id'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_failed_status_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_failed_status_id');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_pending_status_id'])) {
			$data['payment_iopay_checkout_transparente_pix_pending_status_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_pending_status_id'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_pending_status_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_pending_status_id');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_processed_status_id'])) {
			$data['payment_iopay_checkout_transparente_pix_processed_status_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_processed_status_id'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_processed_status_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_processed_status_id');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_refunded_status_id'])) {
			$data['payment_iopay_checkout_transparente_pix_refunded_status_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_refunded_status_id'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_refunded_status_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_refunded_status_id');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_reversed_status_id'])) {
			$data['payment_iopay_checkout_transparente_pix_reversed_status_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_reversed_status_id'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_reversed_status_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_reversed_status_id');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_voided_status_id'])) {
			$data['payment_iopay_checkout_transparente_pix_voided_status_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_voided_status_id'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_voided_status_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_voided_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_geo_zone_id'])) {
			$data['payment_iopay_checkout_transparente_pix_geo_zone_id'] = $this->request->post['payment_iopay_checkout_transparente_pix_geo_zone_id'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_geo_zone_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_status'])) {
			$data['payment_iopay_checkout_transparente_pix_status'] = $this->request->post['payment_iopay_checkout_transparente_pix_status'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_status'] = $this->config->get('payment_iopay_checkout_transparente_pix_status');
		}

		if (isset($this->request->post['payment_iopay_checkout_transparente_pix_sort_order'])) {
			$data['payment_iopay_checkout_transparente_pix_sort_order'] = $this->request->post['payment_iopay_checkout_transparente_pix_sort_order'];
		} else {
			$data['payment_iopay_checkout_transparente_pix_sort_order'] = $this->config->get('payment_iopay_checkout_transparente_pix_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/iopay_checkout_transparente_pix', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/iopay_checkout_transparente_pix')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_iopay_checkout_transparente_pix_email']) {
			$this->error['email'] = $this->language->get('error_email');
		}

		return !$this->error;
	}
}