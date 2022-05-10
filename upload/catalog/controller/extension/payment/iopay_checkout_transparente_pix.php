<?php


class ControllerExtensionPaymentIOPAYCheckoutTransparentePix extends Controller{

    public function index(){

        error_reporting(0);
        ini_set('display_errors', 0);

        $this->load->language('extension/payment/iopay_checkout_transparente_pix');

        $data['text_testmode'] = $this->language->get('text_testmode');
        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['testmode'] = $this->config->get('payment_iopay_checkout_transparente_pix_test');

        if (!$this->config->get('payment_iopay_checkout_transparente_pix_test')) {
            $data['action'] = 'https://checkout.iopay.com.br/app/pay/' . $this->config->get('payment_iopay_checkout_transparente_pix_ioid');
        } else {
            $data['action'] = 'https://sandbox.checkout.iopay.com.br/app/pay/' . $this->config->get('payment_iopay_checkout_transparente_pix_ioid');
        }

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $this->load->model('account/custom_field'); //aqui puxa o model do catalog/account/custom_field
        $custom_fields = $this->model_account_custom_field->getCustomFields();

        $customer_primarydocument_id =  $this->config->get('payment_iopay_checkout_transparente_pix_mapping_user_primarydocument');
        $shipping_primarydocument_id = $customer_primarydocument_id;
        $shipping_number_shipping_id =  $this->config->get('payment_iopay_checkout_transparente_pix_mapping_shipping_addressnumber');

        for($i = 0; $i < count($custom_fields); $i++){

            // cpf customer
            if($custom_fields[$i]['custom_field_id'] == $customer_primarydocument_id){
                $custom_customer_primarydocument = $order_info['custom_field'][$customer_primarydocument_id];
            }

            // number  shipping
            if($custom_fields[$i]['custom_field_id'] == $shipping_number_shipping_id){
                $custom_shipping_number = $order_info['shipping_custom_field'][$shipping_number_shipping_id];
            }

        }
        $custom_shipping_phonenumber = $order_info['telephone'];
        $custom_shipping_primarydocument = $custom_customer_primarydocument;

        if($custom_customer_primarydocument == '' || $custom_shipping_primarydocument == ''){
            print "<H3 style='color:red'>ERRO NA CONFIGURAÇÃO DO MÓDULO IOPAY (Checkout Transparente)</H3>";
            print "<H4>Configure os 'custom fields' de acordo com a documentacão do módulo IOPAY </H4>";
            print "<p>A configuração dos custom fields é necessária para processamento de transações pelo checkout transparente IO</p>";
            exit();
        }

        if ($order_info) {

            $data['business'] = $this->config->get('payment_iopay_checkout_transparente_pix_email');
            $data['item_name'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

            $data['products'] = array();

            foreach ($this->cart->getProducts() as $product) {
                $option_data = array();

                foreach ($product['option'] as $option) {
                    if ($option['type'] != 'file') {
                        $value = $option['value'];
                    } else {
                        $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                        if ($upload_info) {
                            $value = $upload_info['name'];
                        } else {
                            $value = '';
                        }
                    }

                    $option_data[] = array(
                        'name' => $option['name'],
                        'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                    );
                }

                $data['products'][] = array(
                    'name' => htmlspecialchars($product['name']),
                    'model' => htmlspecialchars($product['model']),
                    'price' => $this->currency->format($product['price'], $order_info['currency_code'], false, false),
                    'quantity' => $product['quantity'],
                    'option' => $option_data,
                    'weight' => $product['weight']
                );
            }

            $data['discount_amount_cart'] = 0;

            $total = $this->currency->format($order_info['total'] - $this->cart->getSubTotal(), $order_info['currency_code'], false, false);

            if ($total > 0) {
                $data['products'][] = array(
                    'name' => $this->language->get('text_total'),
                    'model' => '',
                    'price' => $total,
                    'quantity' => 1,
                    'option' => array(),
                    'weight' => 0
                );
            } else {
                $data['discount_amount_cart'] -= $total;
            }

            $data['currency_code'] = $order_info['currency_code'];
            $data['first_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
            $data['last_name'] = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
            $data['address1'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
            $data['address2'] = html_entity_decode($order_info['payment_address_2'], ENT_QUOTES, 'UTF-8');
            $data['city'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
            $data['zip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
            $data['country'] = $order_info['payment_iso_code_2'];
            $data['email'] = $order_info['email'];
            $data['invoice'] = $this->session->data['order_id'] . ' - ' . html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8') . ' ' . html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
            $data['lc'] = $this->session->data['language'];
            $data['return'] = $this->url->link('checkout/success');
            $data['notify_url'] = $this->url->link('extension/payment/iopay_checkout_transparente_pix/callback', '', true);
            $data['cancel_return'] = $this->url->link('checkout/checkout', '', true);

            // Bearer token para tokenizar cartão pelo frontend
            $data['iopay_tokenizecards_token'] = $this -> getIOPayAuthorization('token');

            // IO Seller ID
            $data['iopay_seller_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_ioid');

            // custom field: customer cpf
            $data['custom_customer_primarydocument'] = $custom_customer_primarydocument;
            // custom field: shipping phone_number
            $data['custom_shipping_phonenumber'] = $custom_shipping_phonenumber;
            // custom field: shipping cpf
            $data['custom_shipping_primarydocument'] = $custom_shipping_primarydocument;
            // custom field: shipping (address number)
            $data['custom_shipping_number'] = $custom_shipping_number;

            // Antifraud id (username) - Requerido quando plano de pagamentos conta com antifraude habilitado
            // Usado no front, pelo JS de fingerprint do sistema antifraude
            $data['iopay_antifraud_id'] = $this->config->get('payment_iopay_checkout_transparente_pix_antifraud_id');
            $data['iopay_antifraud_type'] = $this->config->get('payment_iopay_checkout_transparente_pix_antifraud_type');

            // sessid do pedido (usado pelo antifraude). Unico por pedido.
            $data['iopay_antifraud_sessid'] = sha1($order_info['order_id']);

            $data['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

            if (!$this->config->get('payment_iopay_checkout_transparente_pix_transaction')) {
                $data['paymentaction'] = 'authorization';
            } else {
                $data['paymentaction'] = 'sale';
            }

            $data['custom'] = $this->session->data['order_id'];
            $data['customer_phone_number'] = $order_info['telephone'];

            $data['iopay_sec_token'] = $this -> getToken();
            return $this->load->view('extension/payment/iopay_checkout_transparente_pix', $data);
        }
    }

    function getToken(){
        $token = sha1(mt_rand());
        if(!isset($this->session->data['iopay_sec_token'])){
            $this->session->data['iopay_sec_token'] = array($token => 1);
        }
        else{
            $this->session->data['iopay_sec_token'][$token] = 1;
        }
        return $token;
    }

    function isTokenValid($token){
        if(!empty($this->session->data['iopay_sec_token'][$token])){
            $this->session->data['iopay_sec_token'][$token] = null;
            unset($this->session->data['iopay_sec_token'][$token]);
            return true;
        }
        return false;
    }

    public function send() {

       error_reporting(0);
       ini_set('display_errors', 0);

        $postedToken = $this->request->post['iopay_sec_token'];

        if(!empty($postedToken)){
            if(!$this -> isTokenValid($postedToken)){
                print "Request Error!";
                exit();
            }
        }else {
            print "A valid token is needed";
            exit();
        }

        $this->load->language('extension/payment/iopay_checkout_transparente_pix');

        unset($this->session->data['iopay_transaction_id']);
        unset($this->session->data['iopay_ecommerce_order_id']);
        unset($this->session->data['iopay_pix_entropykey']);
        unset($this->session->data['iopay_sandbox_mode']);
        unset($this->session->data['iopay_total_order']);

        $data['text_testmode'] = $this->language->get('text_testmode');
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['testmode'] = $this->config->get('payment_iopay_checkout_transparente_pix_test');

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if ($order_info) {

            // prepare the custom fields
            $this->load->model('account/custom_field');
            $custom_fields = $this->model_account_custom_field->getCustomFields();

            $customer_primarydocument_id =  $this->config->get('payment_iopay_checkout_transparente_pix_mapping_user_primarydocument');
            $shipping_number_shipping_id =  $this->config->get('payment_iopay_checkout_transparente_pix_mapping_shipping_addressnumber');

            for($i = 0; $i < count($custom_fields); $i++){

                // cpf customer
                if($custom_fields[$i]['custom_field_id'] == $customer_primarydocument_id){
                    $custom_customer_primarydocument = $order_info['custom_field'][$customer_primarydocument_id];
                }

                // street number (shipping)
                if($custom_fields[$i]['custom_field_id'] == $shipping_number_shipping_id){
                    $custom_shipping_number = $order_info['shipping_custom_field'][$shipping_number_shipping_id];
                }

            }

            $custom_shipping_phonenumber = $order_info['telephone'];
            $custom_shipping_primarydocument = $custom_customer_primarydocument;

            if($custom_customer_primarydocument == '' || $custom_shipping_primarydocument == ''){
                print "<H3 style='color:red'>ERRO NA CONFIGURAÇÃO DO MÓDULO IOPAY (Checkout Transparente)</H3>";
                print "<H4>Configure os 'custom fields' de acordo com a documentacão do módulo IOPAY </H4>";
                print "<p>A configuração dos custom fields é necessária para processamento de transações pelo checkout transparente IO</p>";
                exit();
            }

            //-- end prepare the custom fields

            // payment type
            if($this->request->post['payment_type'] == 'pix'){
                $payment_type = $this->request->post['payment_type'];
            }else {
                $this->log->write('IOPAY - Erro, tipo de transação invalido. Abortando...');
                exit();
            }

            // make login to retrieve the authorization token
            $auth_token = $this->getIOPayAuthorization();

            /**
            // formats the address and address number
            if(!is_numeric($order_info['payment_address_2'])) {
            if (preg_match('/([0-9\/])/i', $order_info['payment_address_1'], $resultAddress)) {
            $streetName = $resultAddress[1];
            $streetNumber = $resultAddress[2];
            }
            }else {
            $streetName = $order_info['payment_address_1'];
            $streetNumber = $order_info['payment_address_2'];
            }
             */

            // usando o custom field para Numero do endereço
            if($custom_shipping_number != '0' && $custom_shipping_number != ''){

                //$adddress_parts = $this -> getAddressParts($order_info['payment_address_1']);
                $streetName = $order_info['payment_address_1'];
                $streetNumber = $custom_shipping_number;
                $streetComplement = isset($order_info['payment_address_3']) ?? $order_info['payment_address_3'];

                // usando o padrão onde extraimos o numero do endereço na mesma linha do endereço: exemplo: Av Paulista, 1000
            }else {

                $adddress_parts = $this -> getAddressParts($order_info['payment_address_1']);
                $streetName = $adddress_parts['street'];
                $streetNumber = $adddress_parts['number'];
                $streetComplement = $adddress_parts['complement'];

            }

            // formatts the phone number
            $tel = $order_info['telephone'];
            $ddd_tel = substr($tel, 0, 2);
            $tel_cliente = substr($tel, 2);

            // creates a customer/buyer on iopay
            $customerParams = array(
                "first_name" => ($order_info['payment_firstname']),
                "last_name" => ($order_info['payment_lastname']),
                "email" => ($order_info['email']),
                "taxpayer_id" => ($custom_customer_primarydocument),
                "phone_number" => "(" . $ddd_tel . ")" . $tel_cliente,
                "address" => array(
                    "line1" => ($streetName),
                    "line2" => ($streetNumber),
                    "line3" => (isset($order_info['payment_address_3']) ? $order_info['payment_address_3'] : $streetComplement),
                    "neighborhood" => ' ',
                    "city" => ($order_info['payment_city']),
                    "state" => ($order_info['payment_zone_code']),
                    "postal_code" => ($order_info['payment_postcode']),
                ),
            );

            if (!$this->config->get('payment_iopay_checkout_transparente_pix_test')) {
                $customerURI = 'https://api.iopay.com.br/api/v1/customer/new';
            } else {
                $customerURI = 'https://sandbox.api.iopay.com.br/api/v1/customer/new';
            }

            $customer_response = $this->iopayRequest($auth_token, $customerURI, $customerParams);

            // erro on customer creation
            if (isset($customer_response->error)) {

                $this->log->write('IOPAY - Erro ao cadastrar comprador para gerar transação de pagamento ' . $customer_response->error->message . ')');

                $json['error_message'] = $customer_response->error;
                $json['iopay_sec_token'] = $this -> getToken();

            } else if (isset($customer_response->success)) {

                $amount = (int)($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100);
                if($payment_type ==  'pix'){

                    $transactionParams = array(
                        "amount" => $amount,  // valor processado precisa estar em centavos, então multiplicamos por 100
                        "currency" => 'BRL',
                        "description" => "Compra online: " . $order_info['store_name']." (#".$order_info['order_id'].")",
                        "statement_descriptor" => substr($order_info['store_name'],0,12) . " - PEDIDO: " . $order_info['order_id'],
                        "io_seller_id" => $this->config->get('payment_iopay_checkout_transparente_pix_ioid'),
                        "payment_type" => $payment_type,
                        "reference_id" => $order_info['order_id']
                    );

                }

                // adiciona os produtos do carrinho na transação
                $data['products'] = array();

                foreach ($this->cart->getProducts() as $product) {
                    $option_data = array();

                    foreach ($product['option'] as $option) {
                        if ($option['type'] != 'file') {
                            $value = $option['value'];
                        } else {
                            $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                            if ($upload_info) {
                                $value = $upload_info['name'];
                            } else {
                                $value = '';
                            }
                        }

                        $option_data[] = array(
                            'name' => $option['name'],
                            'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value),
                            'quantity' => $option['quantity'],
                            'total' => $option['total']
                        );
                    }

                    $data['products'][] = array(
                        'name' => htmlspecialchars($product['name']),
                        'code' => htmlspecialchars($product['product_id']),
                        'model' => htmlspecialchars($product['model']),
                        'total' => htmlspecialchars($product['total']),
                        'price' => htmlspecialchars($product['price']),
                        'amount' => $this->currency->format($product['total'], $order_info['currency_code'], false, false)*100,
                        'quantity' => $product['quantity'],
                    );

                }

                $transactionParams['products'] = $data['products'];

                if (!$this->config->get('payment_iopay_checkout_transparente_pix_test')) {
                    $transactionURI = 'https://api.iopay.com.br/api/v1/transaction/new/' . $customer_response->success->id;
                } else {
                    $transactionURI = 'https://sandbox.api.iopay.com.br/api/v1/transaction/new/' . $customer_response->success->id;
                }

                $transaction_response = $this->iopayRequest($auth_token, $transactionURI, $transactionParams);

                if(isset($transaction_response->success)){
                    $json = $this -> addSucceededTransaction($transaction_response, $order_info['order_id'],$this->model_checkout_order);
                }else if(isset($transaction_response->error)){
                    $json = $this -> addFailedTransaction($transaction_response, $order_info['order_id'],$this->model_checkout_order);
                }else {
                    $json['error_message'] = $transaction_response->error;
                }

            }
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
    }

    function addSucceededTransaction($transaction_response, $order_id, $model_checkout_order):array {

        if (!$this->config->get('payment_iopay_checkout_transparente_pix_test')) {
            $pix_qrimage = "<img src='https://sandbox.api.iopay.com.br/api/pix/qrcode/".$transaction_response->success->id."' width='190'/>";
        } else {
            $pix_qrimage = "<img src='https://api.iopay.com.br/api/pix/qrcode/".$transaction_response->success->id."' width='190'/>";
        }

        $message = 'Processado com<br><img src="https://static.iopay.dev/assets/img/iopay.png" alt="" width="100px"><hr>';
        $message.= '<h4><i class="fa fa-id-card-o" aria-hidden="true"></i> &nbsp;<strong>Pagamento Instantâneo por PIX</strong></h4><br>';

        if (isset($transaction_response->success->id)) {
            $message .= '<i class="fa fa-check-circle-o" aria-hidden="true"></i> &nbsp;<strong>ID da Transa&ccedil;&atilde;o:</strong><br>' . $transaction_response->success->id . "<br><br>";
        }

        if (isset($transaction_response->success->payment_method->qr_code->emv)) {
            $message .= '<img src="https://checkout.iopay.com.br/checkout/assets/img/pix.png" height="38"><br><br>';
            $message .= '<i class="fa fa-key" aria-hidden="true"></i> &nbsp;<strong>Chave aleat&oacute;ria PIX (Pix Copia e Cola)</strong><br>' . $transaction_response->success->payment_method->qr_code->emv . "<br><br>";
            $message.= "<br>".$pix_qrimage."<br><hr/>";
        }

        if (isset($transaction_response->success->created_at)) {
            $date_generated = date_create($transaction_response->success->created_at);
            $message .= '<i class="fa fa-calendar-check-o" aria-hidden="true"></i> &nbsp;<strong>Data de geração:</strong><br>' . date_format($date_generated,"d/m/Y H:i:s") . "<br><br>";
        }

        if (isset($transaction_response->success->expiration_date)) {
            $date_expiration = date_create($transaction_response->success->expiration_date);
            $message .= '<i class="fa fa-calendar-check-o" aria-hidden="true"></i> &nbsp;<strong>Data limite para pagamento:</strong><br> ' . date_format($date_expiration,"d/m/Y H:i:s") . "<br><br>";
        }

        if (isset($transaction_response->success->reference_id)) {
            $message .= '<i class="fa fa-database" aria-hidden="true"></i> &nbsp;<strong>ID de Referência:</strong><br>' . $transaction_response->success->reference_id . "<br><br>";
        }

        $message .= (isset($transaction_response->success->confirmed) && $transaction_response->success->confirmed == '1' ? '<span class="label label-success">PAGAMENTO CONFIRMADO' : '<span class="label label-warning">AGUARDANDO PAGAMENTO') . "</span><br>";
        $message .= '<h3>R$' . $transaction_response->success->amount . "</h3>";

        if ($transaction_response->success->status == 'pending') {

            $status = $this->config->get('payment_iopay_checkout_transparente_pix_pending_status_id');
            $model_checkout_order->addOrderHistory($order_id, $status, $message, true, true);

        }else if ($transaction_response->success->status == 'succeeded') {

            $status = $this->config->get('payment_iopay_checkout_transparente_pix_processed_status_id');
            $model_checkout_order->addOrderHistory($order_id, $status, $message, true, true);

        }else if ($transaction_response->success->status == 'canceled' || $transaction_response->success->status == 'expired') {

            $status = $this->config->get('payment_iopay_checkout_transparente_pix_failed_status_id');
            $model_checkout_order->addOrderHistory($order_id, $status, $message, true, false);

        }else {
            $status = $this->config->get('payment_iopay_checkout_transparente_pix_failed_status_id');
            $model_checkout_order->addOrderHistory($order_id, $status, $message, true, false);

        }

        $this->session->data['iopay_transaction_id'] = $transaction_response->success->id;
        $this->session->data['iopay_ecommerce_order_id'] = $this->session->data['order_id'];
        $this->session->data['iopay_pix_entropykey'] = $transaction_response->success->payment_method->qr_code->emv;
        $this->session->data['iopay_sandbox_mode'] = $this->config->get('payment_iopay_checkout_transparente_pix_test');
        $this->session->data['iopay_total_order'] = $transaction_response->success->amount;
        $this->session->data['iopay_payment_type'] = 'pix';

        $json['success'] = $this->url->link('checkout/success');
        $json['order_id'] = $this->session->data['order_id'];
        $json['transaction_id'] = $transaction_response->success->id;
        $json['pix_qrcode_url'] = $transaction_response->success->pix_qrcode_url;
        $json['pix_entropy_key'] = $transaction_response->success->payment_method->qr_code->emv;

        return $json;
    }

    function addFailedTransaction($transaction_response, $order_id, $model_checkout_order):array{

        $message = 'Processado com<br><img src="https://static.iopay.dev/assets/img/iopay.png" alt="" width="100px"><hr>';
        $message.= '<h4><i class="fa fa-id-card-o" aria-hidden="true"></i> &nbsp;<strong>Pagamento Instantâneo por PIX</strong></h4><br>';

        if (isset($transaction_response->success->id)) {
            $message .= '<i class="fa fa-check-circle-o" aria-hidden="true"></i> &nbsp;<strong>ID da Transa&ccedil;&atilde;o:</strong><br>' . $transaction_response->success->id . "<br><br>";
        }

        if (isset($transaction_response->success->error->payment_method->qr_code->emv)) {
            $message .= '<img src="https://checkout.iopay.com.br/checkout/assets/img/pix.png" height="38"><br><br>';
            $message .= '<i class="fa fa-key" aria-hidden="true"></i> &nbsp;<strong>Chave aleat&oacute;ria PIX</strong><br>' . $transaction_response->success->payment_method->qr_code->emv . "<br><br>";
        }

        if (isset($transaction_response->success->created_at)) {
            $date_generated = date_create($transaction_response->success->created_at);
            $message .= '<i class="fa fa-calendar-check-o" aria-hidden="true"></i> &nbsp;<strong>Data de geração:</strong><br>' . date_format($date_generated,"d/m/Y H:i:s") . "<br><br>";
        }

        if (isset($transaction_response->success->expiration_date)) {
            $date_expiration = date_create($transaction_response->success->expiration_date);
            $message .= '<i class="fa fa-calendar-check-o" aria-hidden="true"></i> &nbsp;<strong>Data limite para pagamento:</strong><br> ' . date_format($date_expiration,"d/m/Y H:i:s") . "<br><br>";
        }

        if (isset($transaction_response->success->reference_id)) {
            $message .= '<i class="fa fa-database" aria-hidden="true"></i> &nbsp;<strong>ID de Referência:</strong><br>' . $transaction_response->success->reference_id . "<br><br>";
        }

        if($transaction_response->success->status != 'failed') {
            $message .=  '<span class="label label-warning">AGUARDANDO PAGAMENTO</span><br>';
        }else {
            $message .= '<span class="label label-danger">ERRO</span> <br>';
        }

        $message .= '<h3>R$' . $transaction_response->success->amount . "</h3>";
        $status = $this->config->get('payment_iopay_checkout_transparente_pix_failed_status_id');

        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $status, $message, true, true);
        $json['error'] = $transaction_response->success->error;
        $json['error_message'] = $transaction_response->success->error;

        if (isset($transaction_response->success->status)) {

            switch($transaction_response->success->status){
                case 'canceled' :
                case 'failed' :
                    $label_status = 'EXPIRADO';
                    $label_badge = 'label-danger';
                    break;
                default :
                    $label_status = 'COM ERRO';
                    $label_badge = 'label-danger';
                    break;
            }

            $message .= '<span class="label '.$label_badge.'">'.$label_status.'</span><br>';
        }

        $status = $this->config->get('payment_iopay_checkout_transparente_card_failed_status_id');

        // adiciona histórico para o cliente da transação falhada e também notifica por email
        /////// $model_checkout_order->addOrderHistory($order_id, $status, $message, true, true); // não notifica o cliente por email, mas adiciona ao historico do pedido como falhado!

        // não adiciona histórico visível ao cliente e também não notifica o cliente, apenas gera log na admin em order
        $model_checkout_order->addOrderHistory($order_id, $status, $message, false, true); // não notifica o cliente por email, mas adiciona ao historico do pedido como falhado!

        $json['iopay_sec_token'] = $this -> getToken();

        return $json;
    }

    /**
     * Webhook client (callback from IOPAY) for all payment methods (PIX, BOLETO, CREDIT CARD)
     * @date 13/02/2022
     *
     * Exemple request:
     *      index.php?route=extension/payment/iopay_checkout_transparente_card/callback
     *      index.php?route=extension/payment/iopay_checkout_transparente_pix/callback
     *      index.php?route=extension/payment/iopay_checkout_boleto/callback
     *
     *      {
     *          "id":"10edd7d8f9ad4d069ec09916c2454e2e",
     *           "status":"succeeded",
     *           "reference_id":"1145",
     *           "type":"credit"
     *       }
     *
     * @return void
     */
    public function callback(){

        error_reporting(0);
        ini_set('display_errors', 0);

        $json = file_get_contents('php://input');
        $payload = json_decode($json, true);

        if (isset($payload['reference_id'])) {
            $order_id = $payload['reference_id'];
        }else {
            $order_id = 0;
        }

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {

            if (!$this->config->get('payment_iopay_checkout_transparente_card_test')) {
                $uri = 'https://api.iopay.com.br/api/v1/transaction/get/'.$payload['id'];
            } else {
                $uri = 'https://sandbox.api.iopay.com.br/api/v1/transaction/get/'.$payload['id'];
            }

            // make login to retrieve the authorization token
            $auth_token = $this->getIOPayAuthorization();
            $params = [];

            // gets the current transaction status
            $response = $this -> iopayRequest($auth_token, $uri, $params, 'GET');

            if (isset($response -> success) && $response -> success -> reference_id != $order_id) {
                print 'security error';
                exit();
            }

            if (!$response || isset($response -> error)) {
                $this->log->write('iopay_checkout_transparente :: Falha ao tentar obter status da transação (TransactionID' . $payload['id'] . ') (OrderID' . $payload['reference_id'] . ')');
            }

            if ($this->config->get('payment_iopay_checkout_transparente_card_debug')) {
                $this->log->write('iopay_checkout_transparente :: GET TRANSACTION UPDATES' . json_encode($response));
            }

            $order_status_id = $this->config->get('config_order_status_id');

            switch ($response -> success -> status) {
                case 'canceled':
                    $label = "<span style='color:darkred;font-weight: bold'>CANCELADO</span>";
                    $order_status_id = $this->config->get('payment_iopay_checkout_transparente_card_canceled_status_id');
                    break;
                case 'failed':
                    $label = "<span style='color:darkred;font-weight: bold'>FALHADO</span>";
                    $order_status_id = $this->config->get('payment_iopay_checkout_transparente_card_failed_status_id');
                    break;
                case 'pending':
                case 'pre_authorized':
                    $label = "<span style='color:orange;font-weight: bold'>EM ANÁLISE ANTIFRAUDE</span>";
                    $order_status_id = $this->config->get('payment_iopay_checkout_transparente_card_pending_status_id');
                    break;
                case 'succeeded':
                    $label = $response -> success -> captured == '1' ? "<span style='color:green; font-weight: bold'>TRANSAÇÃO APROVADA</span>" : "<span style='color:orange; font-weight: bold'>EM ANÁLISE ANTIFRAUDE</span>";
                    $order_status_id = $this->config->get('payment_iopay_checkout_transparente_card_processed_status_id');
                    break;
                case 'chargeback':
                    $label = "<span style='color:red;font-weight: bold'>CHARGEBACK / ESTORNADO</span>";
                    $order_status_id = $this->config->get('payment_iopay_checkout_transparente_card_refunded_status_id');
                    break;
                case 'void':
                    $label = "<span style='color:darkred;font-weight: bold'>ESTORNADO</span>";
                    $order_status_id = $this->config->get('payment_iopay_checkout_transparente_card_voided_status_id');
                    break;
                default:
                    $label = "<span style='color:darkred;font-weight: bold'>".ucfirst($response -> success -> status)."</span>";
                    $order_status_id = $this->config->get('payment_iopay_checkout_transparente_card_failed_status_id');
                    break;
            }

            // boleto
            if(isset($response -> success -> payment_method -> resource) && $response -> success -> payment_method -> resource  == 'boleto'){

                if ($response->success->status == 'pending' || $response->success->status == 'succeeded') {

                    $message = 'Processado com<br><img src="https://static.iopay.dev/assets/img/iopay.png" alt="" width="100px">';

                    if (isset($response->success->id)) {
                        $message .= '<hr><i class="fa fa-check-circle-o"></i> &nbsp;<strong>ID da Transa&ccedil;&atilde;o:</strong><br>'.$response->success->id.' <br><br>';
                    }

                    $date_boleto_created = date_create($response->success->created_at);
                    $message .= '<i class="fa fa-calendar-check-o"></i>&nbsp;<strong>Data da geração:</strong><br>'.date_format($date_boleto_created, "d/m/Y h:i:s").'<br><br>';

                    if (isset($response->success->payment_method->url)) {
                        $message .= '<i class="fa fa-barcode"></i> &nbsp;<strong>C&oacute;digo de Barras:</strong><br>' . $response->success->payment_method->barcode . "&nbsp;&nbsp;<i class='fa fa-external-link text-primary' onclick='window.location.href=\"".$response->success->payment_method->url."\"' ></i><br><br>";
                    }

                    if (isset($response->success->payment_method->payment_limit_date)) {
                        $date_boleto_limit = date_create($response->success->payment_method->payment_limit_date);
                        $message .= '<i class="fa fa-calendar-check-o"></i> &nbsp;<strong>Data de vencimento:</strong><br> ' . date_format($date_boleto_limit, "d/m/Y h:i:s") . "<br><br>";
                    }

                    $message .= '<h3> R$' . $response->success->amount . "</h3>\n";
                    $message .= (isset($response->success->confirmed) && $response->success->confirmed == '1' ? '<span class="label label-success">PAGAMENTO CONFIRMADO' : '<span class="label label-warning">AGUARDANDO PAGAMENTO') . "</span>";

                    if ($response->success->status == 'pending') {
                        $status = $this->config->get('payment_iopay_checkout_transparente_boleto_pending_status_id');
                    } else if ($response->success->status == 'succeeded') {
                        $status = $this->config->get('payment_iopay_checkout_transparente_boleto_processed_status_id');
                    } else {
                        $status = $this->config->get('payment_iopay_checkout_transparente_boleto_failed_status_id');
                    }

                    if($order_status_id != $order_info['order_status_id']) {
                        $this->model_checkout_order->addOrderHistory($order_id, $status, $message, true, true);
                    }

                    print 'it´s ok';
                }else {

                    $message = 'Processado com<br><img src="https://static.iopay.dev/assets/img/iopay.png" alt="" width="100px">';

                    if (isset($response->error->id)) {
                        $message .= '<hr><i class="fa fa-check-circle-o"></i> &nbsp;<strong>ID da Transa&ccedil;&atilde;o:</strong><br>'.$response->error->id.' <br><br>';
                    }

                    $date_boleto_created = date_create($response->error->created_at);
                    $message .= '<i class="fa fa-calendar-check-o"></i>&nbsp;<strong>Data da geração:</strong><br>'.date_format($date_boleto_created, "d/m/Y h:i:s").'<br><br>';

                    if (isset($response->error->payment_method->payment_limit_date)) {
                        $date_boleto_limit = date_create($response->error->payment_method->payment_limit_date);
                        $message .= '<i class="fa fa-calendar-check-o"></i> &nbsp;<strong>Data de vencimento:</strong><br> ' . date_format($date_boleto_limit, "d/m/Y h:i:s") . "<br><br>";
                    }

                    $message .= '<h3> R$' . $response->success->amount . "</h3>\n";
                    $message .= (isset($response->error->confirmed) && $response->success->confirmed == '1' ? '<span class="label label-success">PAGAMENTO CONFIRMADO' : '<span class="label label-warning">AGUARDANDO PAGAMENTO') . "</span>";

                    $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $message, true);
                }

            }else if(isset($response -> success -> payment_method -> resource) && $response -> success -> payment_method -> resource  == 'card'){

                $message = 'Processado com<br><img src="https://static.iopay.dev/assets/img/iopay.png" alt="" width="100px"><br>';
                $message.= '<hr><h4><i class="fa fa-credit-card-alt" aria-hidden="true"></i> &nbsp;<strong>Cart&atilde;o de Cr&eacute;dito</strong></h4>';

                if (isset($response->success->created_at)) {
                    $date_generated = date_create($response->success->created_at);
                    $message .= '<i class="fa fa-calendar-check-o" aria-hidden="true"></i> &nbsp;<strong>Data de geração:</strong><br>' . date_format($date_generated,"d/m/Y H:i:s") . "<br><br>";
                }

                if (isset($response->success->id)) {
                    $message .= '<i class="fa fa-check-circle-o" aria-hidden="true"></i> &nbsp;<strong>ID da Transa&ccedil;&atilde;o:</strong><br>' . $response->success->id . "<br><br>";
                }

                if (isset($response->success->payment_method)) {
                    $message .= '<i class="fa fa-user-o" aria-hidden="true"></i> &nbsp;<strong>Portador do cart&atilde;o: </strong><br>' . $response->success->payment_method->holder_name . "<br><br>";
                    $message .= '<i class="fa fa-credit-card" aria-hidden="true"></i> &nbsp;<strong>Cart&atilde;o: </strong><br>'.$response->success->payment_method->first4_digits.' **** **** ' . $response->success->payment_method->last4_digits . " &nbsp;&nbsp;&nbsp; (".($response->success->payment_method->card_brand).")<br><br>";
                    $message .= '<i class="fa fa-clock-o" aria-hidden="true"></i> &nbsp;<strong>Validade: </strong><br>' . $response->success->payment_method->expiration_month . "/". $response->success->payment_method->expiration_year . "<br><br>";
                }

                if (isset($response->success->installment_plan)) {
                    $message .= '<i class="fa fa-check-circle-o" aria-hidden="true"></i> &nbsp;<strong>Parcelamento</strong><br>' . $response->success->installment_plan->number_installments . " parcela".($response->success->installment_plan->number_installments == '1' ? '' : 's').'<br><br>';
                }

                if (isset($response->success->sales_receipt)) {
                    $message .= '<i class="fa fa-check-circle-o" aria-hidden="true"></i> &nbsp;<strong>N&ordm; do Comprovante:</strong><br>' . $response->success->sales_receipt . "<br><br>";
                }

                if (isset($response->success->antifraud_status)) {
                    $message .= '<i class="fa fa-shield" aria-hidden="true"></i> &nbsp;<strong>Antifraude:</strong><br>' . $response->success->antifraud_status . "<br><br>";
                }

                if (isset($response->success->reference_id)) {
                    $message .= '<i class="fa fa-database" aria-hidden="true"></i> &nbsp;<strong>ID de Referência:</strong><br>' . $response->success->reference_id . "<br><br>";
                }

                $message .= "<br><br>".$label;

                if (isset($response->success->amount)) {
                    $message .= '<h3>R$' . $response->success->amount . "</h3>";
                }

                $message = str_replace("\"","'", $message);

                // caso haja um status da transação diferente do que o status atual no OpenCart, adicionamos ao historico....
                if($order_status_id != $order_info['order_status_id'] || $order_info['order_status_id'] == '') {
                    $this->model_checkout_order->addOrderHistory($order_id, ($order_status_id), $message, true, true);
                }

                print 'it´s ok';

            }else if(isset($response -> success -> payment_type) && $response -> success -> payment_type  == 'pix'){

                if ($response->success->status == 'pending' || $response->success->status == 'succeeded') {

                    $message = 'Processado com<br><img src="https://static.iopay.dev/assets/img/iopay.png" alt="" width="100px"><hr>';
                    $message.= '<h4><i class="fa fa-id-card-o" aria-hidden="true"></i> &nbsp;<strong>Pagamento Instantâneo por PIX</strong></h4><br>';

                    if (isset($response->success->id)) {
                        $message .= '<i class="fa fa-check-circle-o" aria-hidden="true"></i> &nbsp;<strong>ID da Transa&ccedil;&atilde;o:</strong><br>' . $response->success->id . "<br><br>";
                    }

                    if (isset($response->success->payment_method->qr_code->emv)) {
                        $message .= '<img src="https://checkout.iopay.com.br/checkout/assets/img/pix.png" height="38"><br><br>';
                        $message .= '<i class="fa fa-key" aria-hidden="true"></i> &nbsp;<strong>Chave aleat&oacute;ria PIX</strong><br>' . $response->success->payment_method->qr_code->emv . "<br><br>";
                    }

                    if (isset($response->success->created_at)) {
                        $date_generated = date_create($response->success->created_at);
                        $message .= '<i class="fa fa-calendar-check-o" aria-hidden="true"></i> &nbsp;<strong>Data de geração:</strong><br>' . date_format($date_generated,"d/m/Y H:i:s") . "<br><br>";
                    }

                    if (isset($response->success->expiration_date)) {
                        $date_expiration = date_create($response->success->expiration_date);
                        $message .= '<i class="fa fa-calendar-check-o" aria-hidden="true"></i> &nbsp;<strong>Data limite para pagamento:</strong><br> ' . date_format($date_expiration,"d/m/Y H:i:s") . "<br><br>";
                    }

                    if (isset($response->success->reference_id)) {
                        $message .= '<i class="fa fa-database" aria-hidden="true"></i> &nbsp;<strong>ID de Referência:</strong><br>' . $response->success->reference_id . "<br><br>";
                    }

                    $message .= (isset($response->success->confirmed) && $response->success->confirmed == '1' ? '<span class="label label-success">PAGAMENTO CONFIRMADO' : '<span class="label label-warning">AGUARDANDO PAGAMENTO') . "</span><br>";

                    $message .= '<h3>R$' . $response->success->amount . "</h3>";

                    if ($response->success->status == 'pending') {
                        $status = $this->config->get('payment_iopay_checkout_transparente_pix_pending_status_id');
                    } else if ($response->success->status == 'succeeded') {
                        $status = $this->config->get('payment_iopay_checkout_transparente_pix_processed_status_id');
                    } else {
                        $status = $this->config->get('payment_iopay_checkout_transparente_pix_failed_status_id');
                    }

                    if($order_status_id != $order_info['order_status_id']) {
                        $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $message, true, true);
                    }

                    print 'it´s ok';

                }else {

                    $message = 'Processado com<br><img src="https://static.iopay.dev/assets/img/iopay.png" alt="" width="100px"><hr>';
                    $message.= '<h4><i class="fa fa-id-card-o" aria-hidden="true"></i> &nbsp;<strong>Pagamento Instantâneo por PIX</strong></h4><br>';

                    if (isset($response->id)) {
                        $message .= '<i class="fa fa-check-circle-o" aria-hidden="true"></i> &nbsp;<strong>ID da Transa&ccedil;&atilde;o:</strong><br>' . $response->id . "<br><br>";
                    }

                    if (isset($response->error->payment_method->qr_code->emv)) {
                        $message .= '<img src="https://checkout.iopay.com.br/checkout/assets/img/pix.png" height="38"><br><br>';
                        $message .= '<i class="fa fa-key" aria-hidden="true"></i> &nbsp;<strong>Chave aleat&oacute;ria PIX</strong><br>' . $response->payment_method->qr_code->emv . "<br><br>";
                    }

                    if (isset($response->created_at)) {
                        $date_generated = date_create($response->created_at);
                        $message .= '<i class="fa fa-calendar-check-o" aria-hidden="true"></i> &nbsp;<strong>Data de geração:</strong><br>' . date_format($date_generated,"d/m/Y H:i:s") . "<br><br>";
                    }

                    if (isset($response->expiration_date)) {
                        $date_expiration = date_create($response->expiration_date);
                        $message .= '<i class="fa fa-calendar-check-o" aria-hidden="true"></i> &nbsp;<strong>Data limite para pagamento:</strong><br> ' . date_format($date_expiration,"d/m/Y H:i:s") . "<br><br>";
                    }

                    if (isset($response->reference_id)) {
                        $message .= '<i class="fa fa-database" aria-hidden="true"></i> &nbsp;<strong>ID de Referência:</strong><br>' . $response->reference_id . "<br><br>";
                    }

                    if($response->status == 'pending' || $response->status != 'failed') {
                        $message .= (isset($response->confirmed) && $response->confirmed == '1' ? '<span class="label label-success">PAGAMENTO CONFIRMADO' : '<span class="label label-warning">AGUARDANDO PAGAMENTO') . "</span><br>";
                    }else if($response->status == 'failed'){
                        $message .= '<span class="label label-danger">FALHADO</span><br>';

                    }
                    $message .= '<h3>R$' . $response->amount . "</h3>";

                    $status = $this->config->get('payment_iopay_checkout_transparente_pix_failed_status_id');

                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $order_status_id, $message, true, true);
                }

            }


        }
    }

    /***
     * Obtém um array com as parts:
     *      street (nome da rua, avenida etc)
     *      number (numero da casa) Obs: caso retorne 0 (zero), indica que esse endereço não possui "numero", sendo no formato internacional
     *      complement (demais informações do endereço)
     *
     */
    private function getAddressParts($input_string){

        $address = "";
        $number = "";
        $complement = "";

        $matches = array();
        if(preg_match('/(?P<address>[^\d]+)(?:,| nº| numero| número| núm| num| n| -)(?:[ ]){0,}(?P<number>\d+).?/i', $input_string, $matches)) {
            $address = $matches['address'];
            $number = intval(str_replace(",","",$matches['number']));
        } else { // no number found, it is only address
            $address = $input_string;
        }

        if(preg_match('/(?P<address>[^\d]+) (?P<number>\d+.?)/', $address, $matches)){
            $address = $matches['address'];
            $number = intval(str_replace(",","",$matches['number']));
        } else { // no number found, it is only address
            $address = $input_string;
        }

        if (!empty($number) && strpos($input_string, (string)($number)) !== false){
            $explode_input_string = explode($number, $input_string);
            $address = !empty($explode_input_string[0]) ? trim($explode_input_string[0]) : $address;
            $complement = !empty($explode_input_string[1]) ? trim($explode_input_string[1]) : "";
            $complement =trim(str_replace([",",".","-","/"], " ", $complement));

        }

        if(empty($number)){
            $number = 0;
        }

        return ['original' => $input_string, 'street' => $address, 'number' => $number, 'complement' => $complement];

    }

    /**
     * Obtem o Bearer token para utilização dos recursos da API IOPAY.
     *
     * Quando type 'transaction': Obtém o Bearer token responsável por gerar e manipular transações (esse token desse ser utilizado exclusivamente no backend)
     * Quando type 'token': Obtém o Bearer token responsável por TOKENIZAR cartões de crédito (esse token desse pode ser utilizado no frontend e funciona somente e unicamente para tokenizar cartões)
     *
     * @param string $type (can be: token or transaction)
     * @return mixed
     */
    private function getIOPayAuthorization($type = 'transaction'){

        $this->load->model('checkout/order');

        $credentials = array(
            'io_seller_id' => $this->config->get('payment_iopay_checkout_transparente_pix_ioid'),
            'email' => $this->config->get('payment_iopay_checkout_transparente_pix_email'),
            'secret' => utf8_encode($this->config->get('payment_iopay_checkout_transparente_pix_io_api_secret'))
        );

        $ch = curl_init();

        if($type == 'transaction'){
            //// BASE URLS to manipulate transactions
            if (!$this->config->get('payment_iopay_checkout_transparente_pix_test')) {
                $uri = 'https://api.iopay.com.br/api/auth/login';
            } else {
                $uri = "https://sandbox.api.iopay.com.br/api/auth/login";
            }
        }else if($type == 'token'){
            //// BASE URLS to generate tokenization token (to be used in frontend)
            if (!$this->config->get('payment_iopay_checkout_transparente_pix_test')) {
                $uri = 'https://checkout.iopay.com.br/api/checkout/v1/auth/login';
            } else {
                $uri = "https://sandbox.checkout.iopay.com.br/api/checkout/v1/auth/login";
            }
        }

        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_PORT, 443);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(json_encode($credentials)));
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($credentials) );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        curl_close ($ch);

        $auth = json_decode($server_output);
        $token = $auth->access_token;

        if($token == '' || $token == null){
            return 'Unauthorized';
        }

        if(isset($auth -> error)){
            return $auth -> error;
        }else {
            return $token;
        }

    }

    private function iopayRequest($token, $url, $post, $method = 'POST') {

        header('Content-Type: application/json');

        $ch = curl_init($url);
        $post = json_encode($post);

        $authorization = "Authorization: Bearer ".$token;

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization )); // Inject the token into the header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
        }else if($method == 'GET'){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); // Specify the request method as get
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, ($post));
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects

        $result = curl_exec($ch);

        curl_close($ch);

        return json_decode($result);

    }

}
