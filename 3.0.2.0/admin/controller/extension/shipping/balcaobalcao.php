<?php
class ControllerExtensionShippingBalcaoBalcao extends Controller
{
  private $error = array();

  private $lang_data = array(
    'heading_title',
    'text_edit',
    'text_enabled',
    'text_disabled',
    'text_all_zones',
    'text_none',
    'text_select_all',
    'text_unselect_all',
    'text_get_one',
    'entry_postcode',
    'entry_additional_time',
    'entry_order_status_send',
    'entry_order_status_cancel',
    'entry_order_status_agent',
    'entry_order_status_sent',
    'entry_order_status_destiny',
    'entry_order_status_customer',
    'entry_token',
    'entry_geo_zone',
    'entry_status',
    'entry_sort_order',
    'entry_total',
    'entry_tax',
    'entry_endpoint',
    'help_postcode',
    'help_additional_time',
    'help_order_status_send',
    'help_order_status_cancel',
    'help_order_status_agent',
    'help_order_status_sent',
    'help_order_status_destiny',
    'help_order_status_customer',
    'help_token',
    'help_total',
    'help_tax',
    'help_endpoint',
    'button_save',
    'button_cancel',
  );

  private $error_data = array(
    'warning',
    'postcode',
    'additional_time',
    'token',
    'order_status_send',
    'order_status_cancel',
    'order_status_agent',
    'order_status_sent',
    'order_status_destiny',
    'order_status_customer',
    'endpoint',
    // 'total',
  );

  // fields list & fields default values
  private $fields = array(
    'shipping_balcaobalcao_postcode' => NULL,
    'shipping_balcaobalcao_additional_time' => 0,
    'shipping_balcaobalcao_geo_zone_id' => NULL,
    'shipping_balcaobalcao_status' => NULL,
    'shipping_balcaobalcao_sort_order' => NULL,
    'shipping_balcaobalcao_order_status_send' => array(), // Processing / Processando
    'shipping_balcaobalcao_order_status_cancel' => array(), // Canceled / Cancelado
    'shipping_balcaobalcao_order_status_agent' => NULL, // Shipped / Despachado
    'shipping_balcaobalcao_order_status_sent' => NULL, // Shipped / Despachado
    'shipping_balcaobalcao_order_status_destiny' => NULL, // Complete / Completo
    'shipping_balcaobalcao_order_status_customer' => NULL, // Complete / Completo
    'shipping_balcaobalcao_token' => NULL,
    'shipping_balcaobalcao_total' => NULL,
    'shipping_balcaobalcao_tax' => 1,
    'shipping_balcaobalcao_endpoint' => 'https://prod.balcaobalcao.com.br/api/',
  );

  public function index()
  {
    $this->load->language('extension/shipping/balcaobalcao');
    $this->load->model('localisation/geo_zone');
    $this->load->model('localisation/order_status');
    $this->load->model('setting/setting');

    $this->document->setTitle($this->language->get('heading_title'));
    $this->document->addScript('view/javascript/balcaobalcao/jquery.mask.min.js');
    $this->document->addScript('view/javascript/balcaobalcao/balcaobalcao.js');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('shipping_balcaobalcao', $this->request->post);
      $this->session->data['success'] = $this->language->get('text_success');
      $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
    }

    // set langs data
    foreach ($this->lang_data as $keyname)
      $data[$keyname] = $this->language->get($keyname);

    // set errors data
    foreach ($this->error_data as $keyname)
      $data['error_' . $keyname] =  isset($this->error[$keyname]) ? $this->error[$keyname] : '';

    $data['breadcrumbs'] = array();
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
    );
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_shipping'),
      'href' => $this->url->link('extension/shipping', 'user_token=' . $this->session->data['user_token'], true)
    );
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('shipping/balcaobalcao', 'user_token=' . $this->session->data['user_token'], true)
    );

    $data['action'] = $this->url->link('extension/shipping/balcaobalcao', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

    // set fields & fields default values
    foreach ($this->fields as $keyname => $value) {
      if (isset($this->request->post[$keyname]))
        $data[$keyname] = $this->request->post[$keyname];
      else if ($this->request->post)
        $data[$keyname] = NULL;
      else if ($this->config->get($keyname) !== NULL && $this->config->get($keyname) !== '')
        $data[$keyname] = $this->config->get($keyname);
      else
        $data[$keyname] = $value;
    }

    $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

    $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/shipping/balcaobalcao', $data));
  }

  protected function validate()
  {
    if (!$this->user->hasPermission('modify', 'extension/shipping/balcaobalcao'))
      $this->error['warning'] = $this->language->get('error_permission');

    if (!$this->request->post['shipping_balcaobalcao_token'])
      $this->error['token'] = $this->language->get('error_token');

    if (!preg_match('/^[0-9]{5}-[0-9]{3}$/', $this->request->post['shipping_balcaobalcao_postcode']))
      $this->error['postcode'] = $this->language->get('error_postcode');

    if ($this->request->post['shipping_balcaobalcao_additional_time'] === '')
      $this->error['additional_time'] = $this->language->get('error_additional_time');

    if (!isset($this->request->post['shipping_balcaobalcao_order_status_send']) || isset($this->request->post['shipping_balcaobalcao_order_status_send']) && !$this->request->post['shipping_balcaobalcao_order_status_send'])
      $this->error['order_status_send'] = $this->language->get('error_order_status_send');

    if (!isset($this->request->post['shipping_balcaobalcao_order_status_cancel']) || isset($this->request->post['shipping_balcaobalcao_order_status_cancel']) && !$this->request->post['shipping_balcaobalcao_order_status_cancel'])
      $this->error['order_status_cancel'] = $this->language->get('error_order_status_cancel');

    if (!$this->request->post['shipping_balcaobalcao_order_status_agent'])
      $this->error['order_status_agent'] = $this->language->get('error_order_status_agent');

    if (!$this->request->post['shipping_balcaobalcao_order_status_destiny'])
      $this->error['order_status_destiny'] = $this->language->get('error_order_status_destiny');

    if (!$this->request->post['shipping_balcaobalcao_order_status_sent'])
      $this->error['order_status_sent'] = $this->language->get('error_order_status_sent');

    if (!$this->request->post['shipping_balcaobalcao_order_status_customer'])
      $this->error['order_status_customer'] = $this->language->get('error_order_status_customer');

    if (!$this->request->post['shipping_balcaobalcao_endpoint'])
      $this->error['endpoint'] = $this->language->get('error_endpoint');

    return !$this->error;
  }

  public function install()
  {
    $this->db->query("CREATE TABLE " . DB_PREFIX . "order_balcaobalcao (
        `order_id` int(11) NOT NULL,
        `tracking_code` varchar(255) DEFAULT NULL,
        `name` varchar(500) DEFAULT NULL,
        `address` varchar(500) DEFAULT NULL,
        `price` decimal(8,2) DEFAULT NULL,
        `deadline` varchar(500) DEFAULT NULL,
        `token` text DEFAULT NULL,
        UNIQUE KEY `order_id` (`order_id`),
        UNIQUE KEY `tracking_code` (`tracking_code`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
    ");
    $this->addEvents();
  }

  public function uninstall()
  {
    $this->db->query("DROP TABLE `" . DB_PREFIX . "order_balcaobalcao`");
    $this->deleteEvents();
  }

  public function addEvents()
  {
    $this->load->model('setting/event');
    $this->model_setting_event->addEvent('balcaobalcao_before_add_order', 'catalog/model/checkout/order/addOrder/before', 'extension/module/balcaobalcao/eventBeforeAddOrder');
    $this->model_setting_event->addEvent('balcaobalcao_before_edit_order', 'catalog/model/checkout/order/editOrder/before', 'extension/module/balcaobalcao/eventBeforeEditOrder');
    $this->model_setting_event->addEvent('balcaobalcao_after_add_order', 'catalog/model/checkout/order/addOrder/after', 'extension/module/balcaobalcao/eventAfterAddOrder');
    $this->model_setting_event->addEvent('balcaobalcao_after_edit_order', 'catalog/model/checkout/order/editOrder/after', 'extension/module/balcaobalcao/eventAfterEditOrder');
    $this->model_setting_event->addEvent('balcaobalcao_after_delete_order', 'catalog/model/checkout/order/deleteOrder/after', 'extension/module/balcaobalcao/eventAfterDeleteOrder');
    $this->model_setting_event->addEvent('balcaobalcao_after_add_order_history', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/balcaobalcao/eventAfterAddOrderHistory');
    $this->model_setting_event->addEvent('balcaobalcao_after_api_order_history', 'catalog/controller/api/order/history/after', 'extension/module/balcaobalcao/eventAfterApiOrderHistory');
    $this->model_setting_event->addEvent('balcaobalcao_after_sale_order_delete', 'admin/controller/sale/order/delete/after', 'extension/module/balcaobalcao/eventAfterSaleOrderDelete');
    $this->model_setting_event->addEvent('balcaobalcao_before_view_sale_order_info', 'admin/view/sale/order_info/before', 'extension/module/balcaobalcao/eventBeforeViewSaleOrderInfo');
  }

  public function deleteEvents()
  {
    $this->load->model('setting/event');
    $this->model_setting_event->deleteEventByCode('balcaobalcao_before_add_order');
    $this->model_setting_event->deleteEventByCode('balcaobalcao_before_edit_order');
    $this->model_setting_event->deleteEventByCode('balcaobalcao_after_add_order');
    $this->model_setting_event->deleteEventByCode('balcaobalcao_after_edit_order');
    $this->model_setting_event->deleteEventByCode('balcaobalcao_after_delete_order');
    $this->model_setting_event->deleteEventByCode('balcaobalcao_after_add_order_history');
    $this->model_setting_event->deleteEventByCode('balcaobalcao_after_api_order_history');
    $this->model_setting_event->deleteEventByCode('balcaobalcao_after_sale_order_delete');
    $this->model_setting_event->deleteEventByCode('balcaobalcao_before_view_sale_order_info');
  }

  /**
   * Adiciona novas funcionalidades na tela de visualização do pedido
   *
   * @param int $route
   * @param array $args
   * @param mixed $output
   * @return void
   * @author Fábio Neis <fabio@ezoom.com.br>
   */
  public function eventBeforeViewSaleOrderInfo(&$route, &$args, &$template)
  {
    $this->_log = new Log('balcaobalcao_event.log');
    $this->_log->write('Trigger: eventBeforeViewSaleOrderInfo');

    $this->load->model('extension/shipping/balcaobalcao');
    $this->load->language('extension/shipping/balcaobalcao');
    $this->load->model('setting/setting');

    $bbOrder = $this->model_extension_shipping_balcaobalcao->getBBOrder($args['order_id']);
    $custom_fields = null;

    if ($bbOrder) {
      $custom_fields = [
        [
          'name' => $this->language->get('entry_sale_tracking_code'),
          'value' => $bbOrder['tracking_code'] ? $bbOrder['tracking_code'] : $this->language->get('entry_sale_no_tracking_code')
        ],
        [
          'name' => $this->language->get('entry_sale_shipping'),
          'value' => $bbOrder['name']
        ],
        [
          'name' => $this->language->get('entry_sale_address'),
          'value' => $bbOrder['address']
        ],
        [
          'name' => $this->language->get('entry_sale_price'),
          'value' => 'R$' . number_format($bbOrder['price'], 2, ',', '.')
        ],
        [
          'name' => $this->language->get('entry_sale_deadline'),
          'value' => $bbOrder['deadline']
        ],
        [
          'name' => $this->language->get('entry_sale_tag_print'),
          'value' => '<a href="https://dashboard.balcaobalcao.com.br/etiquetas/' . $bbOrder['tracking_code'] . '" target="_blank">' . $this->language->get('entry_sale_print') . '</a>'
        ]
      ];
    }

    $data = [
      'custom_fields' => $custom_fields,
      'text_sale' => $this->language->get('text_sale')
    ];

    $args['tabs'][] = [
      'code'    => 'balcaobalcao',
      'title'   => 'Balcão Balcão',
      'content' => $this->load->view('extension/shipping/balcaobalcao_order', $data)
    ];
  }
}
