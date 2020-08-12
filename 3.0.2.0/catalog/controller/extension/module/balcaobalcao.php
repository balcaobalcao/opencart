<?php
class ControllerExtensionModuleBalcaobalcao extends Controller
{
  //Before method params (&$route, &$args)
  //After method params (&$route, &$args, &$output)

  public function __construct($registry)
  {
    parent::__construct($registry);
    $this->_log = new Log('balcaobalcao_event.log');

    $this->load->library('balcaobalcao');
    $this->load->model('extension/shipping/balcaobalcao');
    $this->load->language('extension/module/balcaobalcao');
  }

  /**
   * Ajustamos alguns campos antes de inserir o pedido
   *
   * @param string $route
   * @param array $args
   * @return void
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function eventBeforeAddOrder(&$route, &$args)
  {
    $this->_log->write('Trigger: eventBeforeAddOrder');

    if ($this->balcaobalcao->checkIfIsABBOrder($args[0])) {
      $args[0] = $this->balcaobalcao->fixOrderData($args[0]);
    }
  }

  /**
   * Adicionamos as informações extras após inserir o pedido
   *
   * @param string $route
   * @param array $args
   * @param int $output
   * @return void
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function eventAfterAddOrder(&$route, &$args, &$output)
  {
    $this->_log->write('Trigger: eventAfterAddOrder');

    if ($this->balcaobalcao->checkIfIsABBOrder($args[0])) {
      $this->balcaobalcao->addOrder(
        $output,
        $this->session->data['shipping_method']['extras']
      );
    }
  }

  /**
   * Ajustamos alguns campos antes de editar um pedido
   *
   * @param string $route
   * @param array $args
   * @return void
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function eventBeforeEditOrder(&$route, &$args)
  {
    $this->_log->write('Trigger: eventBeforeEditOrder');

    list($order_id, $data) = $args;

    //Se existir, removemos e inserimos novamente se necessário no eventAfterEditOrder
    $bb_order = $this->model_extension_shipping_balcaobalcao->getBBOrder($order_id);
    if ($bb_order) {
      $this->model_extension_shipping_balcaobalcao->deleteBBOrder($order_id);
      $bb_order = null;
    }

    if ($this->balcaobalcao->checkIfIsABBOrder($data)) {
      $args[1] = $this->balcaobalcao->fixOrderData($data);
    }
  }

  /**
   * Adicionamos as informações extras após editar o pedido na tabela order_balcaobalcao
   *
   * @param string $route
   * @param array $args
   * @param mixed $output
   * @return void
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function eventAfterEditOrder(&$route, &$args, &$output)
  {
    $this->_log->write('Trigger: eventAfterEditOrder');

    list($order_id, $data) = $args;
    if ($this->balcaobalcao->checkIfIsABBOrder($data)) {
      $this->balcaobalcao->addOrder(
        $order_id,
        $this->session->data['shipping_method']['extras']
      );
    }
  }

  /**
   * Validamos as informações depois de inserir o histórico
   *
   * @param string $route
   * @param array $args
   * @param mixed $output
   * @return void
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function eventAfterAddOrderHistory(&$route, &$args, &$output)
  {
    $this->_log->write('Trigger: eventAfterAddOrderHistory');

    list($order_id, $order_status_id, $comment, $notify, $override) = $args;

    //Validamos se é um pedido para a balcão balcão
    $bb_order = $this->model_extension_shipping_balcaobalcao->getBBOrder($order_id);
    if ($bb_order) {

      //Valida se é um novo pedido
      if ($this->balcaobalcao->checkIfMustSendOrder($order_id, $order_status_id)) {
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $this->balcaobalcao->sendOrder($order_info, $order_status_id);

        //Valida se é um cancelamento
      } else if ($this->balcaobalcao->checkIfMustCancelOrder($order_id, $order_status_id)) {
        $this->balcaobalcao->updateOrder($order_id, 2);
      }
    }
  }

  /**
   * Altera a mensagem de retorno do Api Order History
   *
   * @param int $route
   * @param array $args
   * @param mixed $output
   * @return void
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function eventAfterApiOrderHistory(&$route, &$args, &$output)
  {
    $this->_log->write('Trigger: eventAfterApiOrderHistory');

    //Sabemos que o output é json. Então, se deu certo, injetamos o retorno da API
    $json = json_decode($this->response->getOutput());
    if (isset($json->success) && isset($this->session->data['balcaobalcao'])) {
      $bbResponse = $this->session->data['balcaobalcao'];
      unset($this->session->data['balcaobalcao']);
      $json->success .= '<br>' . $this->language->get('heading_title') . ': ' . $bbResponse->message;
      $this->response->setOutput(json_encode($json));
    }
  }

  /**
   * Solicita a exclusão do pedido na Balcão Balcão
   *
   * @param int $route
   * @param array $args
   * @param mixed $output
   * @return void
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function eventAfterDeleteOrder(&$route, &$args, &$output)
  {
    $this->_log->write('Trigger: eventAfterDeleteOrder');

    list($order_id) = $args;

    //Validamos se é um pedido para a balcão balcão
    $bb_order = $this->model_extension_shipping_balcaobalcao->getBBOrder($order_id);
    if ($bb_order) {
      $this->balcaobalcao->updateOrder($order_id, 2);
      $this->model_extension_shipping_balcaobalcao->deleteBBOrder($order_id);
    }
  }

  /**
   * Ajusta a mensagem de exclusão do pedido na Balcão Balcão
   *
   * @param int $route
   * @param array $args
   * @param mixed $output
   * @return void
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function eventAfterSaleOrderDelete(&$route, &$args, &$output)
  {
    $this->_log->write('Trigger: eventAfterSaleOrderDelete');

    if (isset($this->session->data['balcaobalcao'])) {
      $bbResponse = $this->session->data['balcaobalcao'];
      unset($this->session->data['balcaobalcao']);
      $this->session->data['success'] .= '<br>' . $this->language->get('heading_title') . ': ' . $bbResponse->message;
    }
  }

  /**
   * Recebe o callback da Balcão Balcão e atualiza as informações do pedido
   *
   * @return json
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function hook()
  {
    //Mudamos o log para o default
    $this->_log = new Log('balcaobalcao.log');
    $this->_log->write('Callback from BB');

    $json = array();

    if (!isset($this->request->server['HTTP_X_BB_AUTHORIZATION']) || $this->request->server['HTTP_X_BB_AUTHORIZATION'] != 'Balcão Balcão') {
      $json['error'] = 'Unauthorized Request';
    } else if (!$this->config->get('balcaobalcao_status')) {
      $json['error'] = $this->language->get('error_status');
    } else {

      $tracking_code = isset($this->request->post['tracking_code']) ? $this->request->post['tracking_code'] : false;
      $order_id = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : false;

      if (!$tracking_code) {
        $json['error'] = $this->language->get('error_code_required');
      } else if (!$order_id) {
        $json['error'] = $this->language->get('error_order_required');
      } else {

        $this->load->model('checkout/order');

        $bb = $this->model_extension_shipping_balcaobalcao->getBBOrder($order_id);
        $order_info = $this->model_checkout_order->getOrder($order_id);

        if (!$bb) {
          $json['error'] = $this->language->get('error_code_invalid');
        } else if (!$order_info) {
          $json['error'] = $this->language->get('error_order_invalid');
        } else if ($bb['tracking_code'] != $tracking_code) {
          $json['error'] = $this->language->get('error_order_invalid');
        } else {

          $status = isset($this->request->post['status_id']) ? $this->request->post['status_id'] : false;
          if (!$status) {
            $json['error'] = $this->language->get('error_order_status_required');
          } else if (!in_array($status, array(3, 4, 5, 6))) { //Agente Origem, Em Trânsito, Agente Destino, Entregue
            $json['error'] = $this->language->get('error_order_status_invalid');
          } else {
            $order_status = false;
            $comment = '';
            switch ($status) {
              case 3:
                $order_status = $this->config->get('balcaobalcao_order_status_agent');
                $comment = sprintf($this->language->get('status_agent'), $tracking_code);
                break;
              case 4:
                $order_status = $this->config->get('balcaobalcao_order_status_sent');
                $comment = $this->language->get('status_sent');
                break;
              case 5:
                $order_status = $this->config->get('balcaobalcao_order_status_destiny');
                $comment = $this->language->get('status_destiny');
                break;
              case 6:
                $order_status = $this->config->get('balcaobalcao_order_status_customer');
                $comment = $this->language->get('status_customer');
                break;
            }

            // Somente altera se possuir status
            if ($order_status) {
              //Para evitar que envie o e-mail da loja não mandamos o parâmetro $notify = true, buscamos o último registro do histórico e alteramos manualmente
              $this->model_checkout_order->addOrderHistory($order_id, $order_status, $comment);
              $this->balcaobalcao->fixOrderHistory($order_id, $order_status, $comment);

              $json['success'] = $this->language->get('text_success');
            } else {
              $json['success'] = $this->language->get('text_accept');
            }
          }
        }
      }
    }

    $this->_log->write('Callback: ' . json_encode($json));

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }
}
