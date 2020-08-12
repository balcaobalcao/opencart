<?php

class BalcaoBalcao
{
  private $api_endpoint;
  private $registry;
  private $_log;

  public function __construct($registry)
  {
    $this->registry = $registry;
    $this->config = $registry->get('config');
    $this->db = $registry->get('db');
    $this->language = $registry->get('language');
    $this->session = $registry->get('session');
    $this->load->language('extension/shipping/balcaobalcao');
    $this->load->model('extension/shipping/balcaobalcao');
    $this->_log = new Log('balcaobalcao.log');

    $this->api_endpoint = $this->config->get('shipping_balcaobalcao_endpoint');
    if (!$this->api_endpoint)
      $this->api_endpoint = 'https://prod.balcaobalcao.com.br/api/';
  }

  /**
   * Make the library supports normal OpenCart functions
   * (like $this->load for languages, models) etc.
   *
   * @param string $name
   * @return mixed
   */
  public function __get($name)
  {
    return $this->registry->get($name);
  }

  public function getData($uri = 'shipping/find', $data)
  {
    $url = $this->api_endpoint . $uri . '?' . http_build_query($data);
    $this->_log->write('Quote requested: ' . $url);
    $api_data = $this->getAPIData($url);
    $json = json_decode($api_data);

    if (isset($json->errors)) {
      $msg = array();
      foreach ($json->errors as $key => $error) {
        $msg[] = $error[0];
        $this->_log->write($error[0]);
      }
      $json = json_decode($this->_force_error(implode(' - ', $msg), $json->status_code));
    };

    return $json;
  }

  public function post($uri = 'order', $data, $method = 'POST')
  {
    $query = http_build_query($data);
    $url = $this->api_endpoint . $uri;
    $this->_log->write('Post data: ' . $url . '?' . $query);
    $api_data = $this->postAPIData($url, $query, $method);
    $json = json_decode($api_data);
    return $json;
  }

  private function _force_error($message, $code = 408)
  {
    $json = json_encode([
      'status_code' => $code,
      'message' => $message,
    ]);

    return $json;
  }

  public function getAPIData($url)
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'X-BB-ApiToken: ' . $this->config->get('shipping_balcaobalcao_token')
    ]);

    $server_output = curl_exec($ch);
    if (!$server_output) {

      $this->_log->write($this->language->get('connection_error'));

      $server_output = curl_exec($ch);

      if ($server_output) {
        $this->_log->write($this->language->get('text_sucesso'));
      } else {
        $lang_reconnection_error = $this->language->get('reconnection_error');
        $this->_log->write($lang_reconnection_error);
        $server_output = $this->_force_error($lang_reconnection_error);
      }
    }

    curl_close($ch);

    return $server_output;
  }

  public function postAPIData($url, $data, $method)
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'X-BB-ApiToken: ' . $this->config->get('shipping_balcaobalcao_token')
    ]);

    $server_output = curl_exec($ch);

    if (!$server_output) {

      $this->_log->write($this->language->get('connection_error'));

      $server_output = curl_exec($ch);

      if ($server_output) {
        $this->_log->write($this->language->get('text_sucesso'));
      } else {
        $lang_reconnection_error = $this->language->get('reconnection_error');
        $this->_log->write($lang_reconnection_error);
        $server_output = $this->_force_error($lang_reconnection_error);
      }
    }

    curl_close($ch);

    return $server_output;
  }

  /**
   * Verifica se contém a palavra balcabalcao no shipping_code do pedido.
   *
   * @param array $order_info
   * @return bool
   */
  public function checkIfIsABBOrder($order_info)
  {
    return strpos($order_info['shipping_code'], 'balcaobalcao') !== FALSE;
  }

  /**
   * Verifica se deve enviar um pedido através do status configurado.
   * Certificando também que não exista o código de rastreio
   *
   * @param int $order_id
   * @param int $status_id
   * @return bool
   */
  public function checkIfMustSendOrder($order_id, $status_id, $situation = 'store')
  {
    $res = false;
    $send_status = $this->config->get('shipping_balcaobalcao_order_status_send');
    //Valida se o status está dentre os definidos
    if ($send_status && in_array($status_id, $send_status)) {

      //Valida se já foi integrado
      $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "order_balcaobalcao WHERE tracking_code IS NOT NULL AND order_id = '" . (int) $order_id . "'");
      $res = ($query->row['total'] == 0);

      if ($situation == 'activate' && $query->row['total'] == 1)
        $res = true;
    }

    return $res;
  }

  /**
   * Verifica se deve cancelar um pedido através do status configurado.
   *
   * @param int $order_id
   * @param int $status_id
   * @return bool
   */
  public function checkIfMustCancelOrder($order_id, $status_id)
  {
    $res = false;
    $cancel_status = $this->config->get('shipping_balcaobalcao_order_status_cancel');
    if ($cancel_status && in_array($status_id, $cancel_status)) {
      $bb_order = $this->model_extension_shipping_balcaobalcao->getBBOrder($order_id);
      if ($bb_order['tracking_code'])
        $res = true;
    }
    return $res;
  }

  /**
   * Ajusta os dados do shipping_method e totals antes de salvar no db
   * @param array $data
   * @return array
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function fixOrderData($data)
  {
    $data['shipping_extras'] = $this->session->data['shipping_method']['extras'];
    $extra = json_decode($data['shipping_extras'], true);
    if (isset($extra['name'])) {
      $data['shipping_method'] = trim($extra['name']);
      if (isset($data['totals'])) {
        foreach ($data['totals'] as $key => $total) {
          if ($total['code'] == 'shipping') {
            $data['totals'][$key]['title'] = trim($extra['name']);
          }
        }
      }
    }
    return $data;
  }

  /**
   * Seta para notificado o histórico do pedido quando vem o retorno da api
   * Serve apenas para exibir o texto do histórico para o cliente mas não enviar o e-mail da loja
   *
   * @param int $order
   * @param int $order_status_id
   * @param string $comment
   * @return boolean
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function fixOrderHistory($order_id, $order_status_id, $comment = '')
  {
    $query = $this->db->query("
            UPDATE " . DB_PREFIX . "order_history SET notify = 1
            WHERE order_id = '" . (int) $order_id . "'
            AND order_status_id = '" . (int) $order_status_id . "'
            AND notify = 0
            AND comment = '" . $this->db->escape($comment) . "'
            ORDER BY date_added DESC
            LIMIT 1
        ");

    return $query;
  }

  /**
   * Envia um pedido pro Balcão Balcão.
   *
   * @param array $order_info
   * @param int $order_status_id
   * @return object
   */
  public function sendOrder($order_info, $order_status_id)
  {
    $bb_order = $this->model_extension_shipping_balcaobalcao->getBBOrder($order_info['order_id']);

    // Prepara o nome do usuário
    if (trim($order_info['shipping_firstname']))
      $customer_name = $order_info['shipping_firstname'] . ' ' . $order_info['shipping_lastname'];
    else
      $customer_name = $order_info['firstname'] . ' ' . $order_info['lastname'];

    // Define o endereço
    $address = trim($order_info['shipping_address_1'] . ' ' . $order_info['shipping_address_2']);

    // Pega informações do custom_field
    $this->load->model('account/custom_field');
    $customer_info = $this->model_account_customer->getCustomer($order_info['customer_id']);
    $data['custom_fields'] = $this->model_account_custom_field->getCustomFields($customer_info['customer_group_id']);

    $document = null;
    $address_number = null;
    $address_complement = null;

    //Order Custom Fields
    if ($order_info['custom_field']) {
      foreach ($data['custom_fields'] as $custom_fields) {
        if (preg_match("/(cpf|cnpj)/i", $custom_fields['name'])) {
          $document = isset($order_info['custom_field'][$custom_fields['custom_field_id']]) ?
            $order_info['custom_field'][$custom_fields['custom_field_id']] : null;
        } else if (preg_match("/(numero|número)/i", $custom_fields['name'])) {
          $address_number = isset($order_info['custom_field'][$custom_fields['custom_field_id']]) ?
            $order_info['custom_field'][$custom_fields['custom_field_id']] : null;
        } else if (preg_match("/complemento/i", $custom_fields['name'])) {
          $address_complement = isset($order_info['custom_field'][$custom_fields['custom_field_id']]) ?
            $order_info['custom_field'][$custom_fields['custom_field_id']] : null;
        }
      }
    }

    //Order Shipping Custom Fields
    if ($order_info['shipping_custom_field']) {
      foreach ($data['custom_fields'] as $custom_fields) {
        if (preg_match("/(cpf|cnpj)/i", $custom_fields['name'])) {
          $document = isset($order_info['shipping_custom_field'][$custom_fields['custom_field_id']]) ?
            $order_info['shipping_custom_field'][$custom_fields['custom_field_id']] : null;
        } else if (preg_match("/(numero|número)/i", $custom_fields['name'])) {
          $address_number = isset($order_info['shipping_custom_field'][$custom_fields['custom_field_id']]) ?
            $order_info['shipping_custom_field'][$custom_fields['custom_field_id']] : null;
        } else if (preg_match("/complemento/i", $custom_fields['name'])) {
          $address_complement = isset($order_info['shipping_custom_field'][$custom_fields['custom_field_id']]) ?
            $order_info['shipping_custom_field'][$custom_fields['custom_field_id']] : null;
        }
      }
    }

    //Se o número do endereço ainda assim for nulo tentamos pela última vez no pedido
    if (empty($address_number))
      $address_number = preg_replace('/[\s]{2,}/', ' ', trim(preg_replace('/\D/', ' ', $order_info['shipping_address_1'])));

    //Se não encontrar o complemento, pegamos o valor do campo padrão do opencart
    if (empty($address_complement))
      $address_complement = trim($order_info['shipping_company']);

    //Se ainda for nulo, tentamos mais uma vez, mas agora buscando pelo custom_field do cliente
    //Caso tenham atualizado o cadastro do cliente depois do pedido efetuado
    if (empty($document) || empty($address_number) || empty($address_complement)) {
      $customer_info['custom_field'] = json_decode($customer_info['custom_field'], true);

      // Customer Custom Fields
      foreach ($data['custom_fields'] as $custom_fields) {
        if (empty($document) && preg_match("/(cpf|cnpj)/i", $custom_fields['name'])) {
          $document = isset($customer_info['custom_field'][$custom_fields['custom_field_id']]) ?
            $customer_info['custom_field'][$custom_fields['custom_field_id']] : null;
        } else if (empty($address_number) && preg_match("/(numero|número)/i", $custom_fields['name'])) {
          $address_number = isset($customer_info['custom_field'][$custom_fields['custom_field_id']]) ?
            $customer_info['custom_field'][$custom_fields['custom_field_id']] : null;
        } else if (empty($address_complement) && preg_match("/complemento/i", $custom_fields['name'])) {
          $address_complement = isset($customer_info['custom_field'][$custom_fields['custom_field_id']]) ?
            $customer_info['custom_field'][$custom_fields['custom_field_id']] : null;
        }
      }


      //Não tem na model o método que retorne o endereço do pedido
      $query = $this->db->query("
                SELECT custom_field
                FROM " . DB_PREFIX . "address
                WHERE customer_id = '" . (int) $order_info['customer_id'] . "'
                AND postcode = '" . $order_info['shipping_postcode'] . "'
                LIMIT 1
            ");

      $customer_info['address_custom_field'] = json_decode($query->row['custom_field'], true);

      // Customer Address Custom Fields
      foreach ($data['custom_fields'] as $custom_fields) {
        if (empty($document) && preg_match("/(cpf|cnpj)/i", $custom_fields['name'])) {
          $document = isset($customer_info['address_custom_field'][$custom_fields['custom_field_id']]) ?
            $customer_info['address_custom_field'][$custom_fields['custom_field_id']] : null;
        } else if (empty($address_number) && preg_match("/(numero|número)/i", $custom_fields['name'])) {
          $address_number = isset($customer_info['address_custom_field'][$custom_fields['custom_field_id']]) ?
            $customer_info['address_custom_field'][$custom_fields['custom_field_id']] : null;
        } else if (empty($address_complement) && preg_match("/complemento/i", $custom_fields['name'])) {
          $address_complement = isset($customer_info['address_custom_field'][$custom_fields['custom_field_id']]) ?
            $customer_info['address_custom_field'][$custom_fields['custom_field_id']] : null;
        }
      }
    }

    // Get Products
    $products = $this->model_extension_shipping_balcaobalcao->getOrderProducts($order_info['order_id']);

    // Prepare Product Data
    $products_data = array();
    foreach ($products as $product) {
      $product['width']  = $this->model_extension_shipping_balcaobalcao->getSizeInMeters($product['length_class_id'], $product['width']);
      $product['height'] = $this->model_extension_shipping_balcaobalcao->getSizeInMeters($product['length_class_id'], $product['height']);
      $product['length'] = $this->model_extension_shipping_balcaobalcao->getSizeInMeters($product['length_class_id'], $product['length']);
      $product['weight'] = $this->model_extension_shipping_balcaobalcao->getWeightInKg($product['weight_class_id'], $product['weight']);

      $products_data[] = array(
        'name'     => $product['name'],
        'quantity' => $product['quantity'],
        'price'    => $product['price'],
        'width'    => $product['width'],
        'height'   => $product['height'],
        'length'   => $product['length'],
        'weight'   => $product['weight'],
      );
    }

    // Prepare Post Data
    $data = array(
      'return_url' => HTTPS_SERVER . 'index.php?route=extension/module/balcaobalcao/hook',
      'customer'   => array(
        'name'               => $customer_name,
        'document'           => $document,
        'email'              => $order_info['email'],
        'phone'              => $order_info['telephone'],
        'address'            => $address,
        'address_number'     => $address_number,
        'address_complement' => $address_complement
      ),
      'order' => array(
        'id'       => $order_info['order_id'],
        'value'    => $order_info['total'],
        'date'     => $order_info['date_added'],
        'token'    => $bb_order['token'],
        'products' => $products_data,
      ),
    );

    // Post Data
    $post_data = $this->post('order/store', $data);

    // If success
    if ($post_data && isset($post_data->tracking_code)) {
      // Update tracking code with the returned code
      $this->model_extension_shipping_balcaobalcao->updateTrackingCodeOrder($post_data->tracking_code, $order_info['order_id']);

      // if tag is "already paid"
      if ($post_data->status == 1) {
        $notify = 1;
        $comment = sprintf($this->language->get('text_comment'), $post_data->tracking_code);
      } else {
        $notify = 0;
        $comment = sprintf($this->language->get('text_tag'), $post_data->tracking_code, $post_data->tracking_code);
      }

      // Add one additional order history for tracking code
      $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int) $order_info['order_id'] . "', order_status_id = '" . (int) $order_status_id . "', notify = '" . (int) $notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
    }

    // Log
    $this->_log->write('sendOrder return: ' . json_encode($post_data));

    // Store Post Data At Session
    $this->session->data['balcaobalcao'] = $post_data;

    // Return Post Data
    return $post_data;
  }

  /**
   * Atualiza o status de um pedido no Balcão Balcão.
   *
   * @param int $order_id
   * @return object
   */
  public function updateOrder($order_id, $status)
  {
    // Get BB Token
    $token = $this->config->get('shipping_balcaobalcao_token');

    $bb_order = $this->model_extension_shipping_balcaobalcao->getBBOrder($order_id);

    // Prepare Data
    $data = array(
      'status' => $status,
    );

    // Post Data
    $post_data = $this->post('order/status/' . $bb_order['tracking_code'], $data, 'PATCH');
    if (isset($post_data->status) && $post_data->status == 1 && $status == 2)
      $this->model_extension_shipping_balcaobalcao->updateTrackingCodeOrder(null, $order_id);

    // Log
    $this->_log->write('updateOrder return: ' . json_encode($post_data));

    // Store Post Data At Session
    $this->session->data['balcaobalcao'] = $post_data;

    // Return Post Data
    return $post_data;
  }

  /**
   * Salva no banco de dados a forma de transporte escolhida
   * @param [int] $order_id
   * @param [json] $shipping_extras
   * @return void
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function addOrder($order_id, $shipping_extras)
  {
    $shipping_extras = json_decode($shipping_extras, true);
    $params = array_merge(
      array(
        'order_id'      => $order_id,
        'tracking_code' => 'NULL',
        'name'          => 'NULL',
        'address'       => 'NULL',
        'price'         => 0.00,
        'deadline'      => 'NULL',
        'token'         => 'NULL',
      ),
      $shipping_extras
    );

    //Organiza o array caso venha valores inválidos
    $params = $this->fixBeforeAddOrder($params);

    $this->_log->write('addOrder: ' . json_encode(array('order_id' => $order_id, 'extras' => $shipping_extras, 'params' => $params)));
    $this->model_extension_shipping_balcaobalcao->addOrder($params);
  }

  /**
   * Altera valores vazios por nulos
   *
   * @param Array $params
   * @return Array
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function fixBeforeAddOrder($params)
  {
    $params = array_map(function ($item) {
      if (is_array($item))
        return $this->fixBeforeAddOrder($item);
      else
        return (trim($item) == '' || $item === NULL) ? 'NULL' : $item;
    }, $params);

    return $params;
  }
}
