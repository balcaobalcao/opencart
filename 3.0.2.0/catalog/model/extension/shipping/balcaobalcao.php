<?php
class ModelExtensionShippingBalcaoBalcao extends Model
{
  private $quote_data = array();
  private $errors = array();

  public function getQuote($address)
  {
    $this->load->language('extension/shipping/balcaobalcao');
    $this->load->library('balcaobalcao');

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('shipping_balcaobalcao_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");
    if (!$this->config->get('shipping_balcaobalcao_geo_zone_id') || $query->num_rows)
      $status = true;
    else
      $status = false;

    $method_data = array();

    if ($status) {

      // don't show Balcão Balcão's module if the minimum value is not matched
      $subtotal = $this->cart->getSubTotal();
      if ($subtotal < $this->config->get('shipping_balcaobalcao_total'))
        return $method_data;

      // get total weight
      $products = $this->cart->getProducts();

      //Adicionar ao tempo qualquer outro valor ex.: Caso exista prazo de fabricação por produto
      $additional_time = (int) $this->config->get('shipping_balcaobalcao_additional_time');

      $products_data = [];
      foreach ($products as $key => $product) {

        // Converte para metros, medidas são unitárias
        $product['width']  = $this->getSizeInMeters($product['length_class_id'], $product['width']);
        $product['height'] = $this->getSizeInMeters($product['length_class_id'], $product['height']);
        $product['length'] = $this->getSizeInMeters($product['length_class_id'], $product['length']);

        // O peso do produto não é unitário como a dimensão, é multiplicado pela quantidade.
        $product['weight'] = $this->getWeightInKg($product['weight_class_id'], $product['weight']) / $product['quantity'];

        $products_data[$key] = [
          'quantity' => $product['quantity'],
          'weight'   => $product['weight'],
          'length'   => $product['length'],
          'width'    => $product['width'],
          'height'   => $product['height'],
        ];
      }

      $api_data = [
        'from'            => $this->config->get('shipping_balcaobalcao_postcode'),
        'to'              => $address['postcode'],
        'value'           => $subtotal,
        'products'        => $products_data,
        'additional_time' => $additional_time,
        'tax'             => $this->config->get('shipping_balcaobalcao_tax'),
      ];

      $json = $this->balcaobalcao->getData('shipping/find', $api_data);

      if ($json && isset($json->status) && $json->status && isset($json->data) && count($json->data)) {
        foreach ($json->data as $key => $row) {
          $this->quote_data[$key] = array(
            'code'         => 'balcaobalcao.' . $key,
            'title'        => $this->language->get('text_title') . ' - ' . $row->name . ' - ' . $row->deadline . '<br/>' . $row->address,
            'cost'         => $row->price,
            'tax_class_id' => 0,
            'text'         => $this->currency->format($row->price, $this->session->data['currency']),
            'extras'       => json_encode($row),
          );
        }
      } else if (isset($json->errors) && is_array($json->errors)) {
        $this->errors = array_merge($this->errors, $json->errors);
      } else if (isset($json->message)) {
        $this->errors[] = $json->message;
        if (isset($json->errors) && is_object($json->errors)) {
          foreach ($json->errors as $error) {
            $this->errors[] = array_pop($error);
          }
        }
      } else {
        $this->errors[] = $this->language->get('error_unknown');
      }

      $error = $this->errors ? implode(' - ', $this->errors) : false;

      if ($error) {
        $this->log->write(sprintf($this->language->get('error_found'), $error));
        return $method_data;
      }

      $method_data = array(
        'code' => 'balcaobalcao',
        'title' => $this->language->get('text_title'),
        'quote' => $this->quote_data,
        'sort_order' => $this->config->get('shipping_balcaobalcao_sort_order'),
        'error' => $error,
      );
    }

    return $method_data;
  }

  /**
   * Get order products.
   *
   * @param int $order_id
   * @return array
   */
  public function getOrderProducts($order_id)
  {
    $query = $this->db->query("
            SELECT
                op.*,
                p.weight,
                p.weight_class_id,
                p.length,
                p.width,
                p.height,
                p.length_class_id
            FROM " . DB_PREFIX . "order_product as op
            LEFT JOIN " . DB_PREFIX . "product as p ON (p.product_id = op.product_id)
            WHERE order_id = '" . (int) $order_id . "'
        ");

    return $query->rows;
  }

  /**
   * Get BB order additional data.
   *
   * @param int $order_id
   * @return array
   */
  public function getBBOrder($order_id)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_balcaobalcao WHERE order_id = '" . (int) $order_id . "' LIMIT 1");

    return $query->row;
  }

  /**
   * Delete BB order.
   *
   * @param int $order_id
   * @return array
   */
  public function deleteBBOrder($order_id)
  {
    $query = $this->db->query("DELETE FROM " . DB_PREFIX . "order_balcaobalcao WHERE order_id = '" . (int) $order_id . "' LIMIT 1");
    return $query;
  }

  // retorna a dimensão em metros
  public function getSizeInMeters($unidade_id, $dimensao)
  {
    if (is_numeric($dimensao)) {
      $length_class_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "length_class mc LEFT JOIN " . DB_PREFIX . "length_class_description mcd ON (mc.length_class_id = mcd.length_class_id) WHERE mcd.language_id = '" . (int) $this->config->get('config_language_id') . "' AND mc.length_class_id =  '" . (int) $unidade_id . "'");
      if (isset($length_class_product_query->row['unit'])) {
        if ($length_class_product_query->row['unit'] == 'mm') { //Milímetro
          $dimensao = $dimensao / 1000;
        } else if ($length_class_product_query->row['unit'] == 'cm') { // Centímetro
          $dimensao = $dimensao / 100;
        } else if ($length_class_product_query->row['unit'] == 'in') { // Polegada
          $dimensao = $dimensao / 39.37;
        }
      }
    }

    return (float) $dimensao;
  }

  // retorna o peso em quilogramas
  public function getWeightInKg($unidade_id, $weight)
  {

    if (is_numeric($weight)) {
      $weight_class_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "weight_class wc LEFT JOIN " . DB_PREFIX . "weight_class_description wcd ON (wc.weight_class_id = wcd.weight_class_id) WHERE wcd.language_id = '" . (int) $this->config->get('config_language_id') . "' AND wc.weight_class_id =  '" . (int) $unidade_id . "'");

      if (isset($weight_class_product_query->row['unit'])) {
        if ($weight_class_product_query->row['unit'] == 'g') { //Gramas
          $weight = $weight / 1000;
        } else if ($weight_class_product_query->row['unit'] == 'oz') { //Onça
          $weight = $weight / 35.274;
        } else if ($weight_class_product_query->row['unit'] == 'lb') { //Líbra
          $weight = $weight / 2.205;
        }
      }
    }
    return (float) $weight;
  }

  //Adiciona o pedido na tabela do balcao balcao
  public function addOrder($params)
  {
    return $this->db->query(
      "INSERT INTO " . DB_PREFIX . "order_balcaobalcao SET
            order_id = " . (int) $params['order_id'] . ",
            tracking_code = " . $this->db->escape($params['tracking_code']) . ",
            name = '" . $this->db->escape($params['name']) . "',
            address = '" . $this->db->escape($params['address']) . "',
            price = '" . (float) $params['price'] . "',
            deadline = '" . $this->db->escape($params['deadline']) . "',
            token = '" . $this->db->escape($params['token']) . "'"
    );
  }

  //Atualiza o tracking_code do pedido na tabela do balcao balcao
  public function updateTrackingCodeOrder($tracking_code, $order_id)
  {
    $this->db->query("UPDATE `" . DB_PREFIX . "order_balcaobalcao` SET tracking_code = " . (($tracking_code === NULL) ? 'NULL' : "'" . $this->db->escape($tracking_code) . "'") . " WHERE order_id = '" . (int) $order_id . "'");
  }
}
