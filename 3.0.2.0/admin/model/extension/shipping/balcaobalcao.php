<?php
class ModelExtensionShippingBalcaoBalcao extends Model
{
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
}
