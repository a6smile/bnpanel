<?php


//Check if called by script
if(THT != 1){
	die();
}

class addon {
	
	
	/**
	 * Gets all adddons by billing cycle id
	 * @param	int		billing id
	 * @return 	array	a list with all addons
 	 * @author	Julio Montoya <gugli100@gmail.com> BeezNest 2010 
	 */
	public function getAllAddonsByBillingCycleAndPackage($billing_id, $package_id) {
		global $db;
		$addong_list = array();		//&& !empty($package_id)
		if (!empty($billing_id) ) {		
			$sql = "SELECT a.id, a.name, amount, bc.name  as billing_name  FROM `<PRE>addons` a INNER JOIN `<PRE>billing_products` b ON (a.id = b.product_id) INNER JOIN `<PRE>billing_cycles` bc
					ON (bc.id = b.billing_id) INNER JOIN `<PRE>package_addons` pa ON (pa.addon_id= a.id) WHERE bc.id = {$billing_id} AND pa.package_id  = {$package_id}";
			$addons_billing = $db->query($sql);
			$addong_list = array();
			while($data = $db->fetch_array($addons_billing)) {
				$addong_list[$data['id']] = array('id'=>$data['id'],  'name' => $data['name'], 'amount'=>$data['amount']);									
			}
		}
		return $addong_list;
	}
	
	
	public function getAllAddonsByBillingId($billing_id) {
		global $db;
		$addong_list = array();		
		if (!empty($billing_id)) {		
			$sql = "SELECT a.id, a.name, amount, bc.name  as billing_name  FROM `<PRE>addons` a INNER JOIN `<PRE>billing_products` b ON (a.id = b.product_id) INNER JOIN `<PRE>billing_cycles` bc
					ON (bc.id = b.billing_id) WHERE bc.id = {$billing_id} ";
			$addons_billing = $db->query($sql);
			$addong_list = array();
			while($data = $db->fetch_array($addons_billing)) {
				$addong_list[$data['id']] = array('id'=>$data['id'],  'name' => $data['name'], 'amount'=>$data['amount']);									
			}
		}
		return $addong_list;
	}
	

	
	
	/**
	 * Generates a serialized value of the addons payments
	 * 			 
	 * Addon_fee structure
	 * array
		  0 => 
		    array
		      'addon_id' 	=> int 9
		      'billing_id'  => string '3' 
		      'amount'		=> string '10.600000'			 
	 * 
	 * @param	array	list of addon ids
	 * @param	int		billing id
	 * @param	bool	the array will be serialize true or false?
	 * @return 	mixed 	array or a string 
	 * @author	Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */
	public function generateAddonFee($list_of_addons_ids, $billing_id, $serialize = false) {
		global $db;
		$addon_fee = array();
		$billing_id = intval($billing_id);
		if (is_array($list_of_addons_ids) && count($list_of_addons_ids) > 0 ) {
			foreach ($list_of_addons_ids as $addon_id) {
				if (is_numeric($addon_id)) {
					$addon_id = intval($addon_id);
					$sql_select 		= "SELECT amount  FROM `<PRE>billing_products` WHERE product_id = $addon_id AND type = '".BILLING_TYPE_ADDON."' AND billing_id = $billing_id ";
					$result 			= $db->query($sql_select);
					$data_amount_addon 	= $db->fetch_array($result);							
					$addon_fee[] = array('addon_id'=>$addon_id,'billing_id'=>$billing_id, 'amount'=> $data_amount_addon['amount']);
				}
			}
			if ($serialize == true) {
				$addon_fee = serialize($addon_fee);
			}
		}
		return $addon_fee;
	}	
	
	public function generateAddonFeeFromList($list_of_addons, $billing_id, $serialize = false) {
		global $db, $currency;
		$addon_fee = array();
		$billing_id = intval($billing_id);
		if (is_array($list_of_addons) && count($list_of_addons) > 0 ) {
			foreach ($list_of_addons as $addon_id=>$amount) {
				if (is_numeric($addon_id)) {
					$addon_id = intval($addon_id);												
					$addon_fee[] = array('addon_id'=>$addon_id,'billing_id'=>$billing_id, 'amount'=> $currency->toFloat($amount));
				}
			}
			if ($serialize == true) {
				$addon_fee = serialize($addon_fee);
			}
		}
		return $addon_fee;
	}	
	
	
	
	public function updateAddonOrders($list_of_addons_ids, $order_id) {
		global $db;
		$addon_fee = array();		
		$result = $db->query("DELETE FROM `<PRE>user_pack_addons` WHERE order_id = ".intval($order_id));
		
		if (is_array($list_of_addons_ids) && count($list_of_addons_ids) > 0 ) {
			foreach ($list_of_addons_ids as $addon_id) {
				if (is_numeric($addon_id)) {
					$addon_id = intval($addon_id);
					$result = $db->query("INSERT INTO `<PRE>user_pack_addons` (addon_id, order_id) VALUES ('{$addon_id}', '{$order_id}')");				
				}
			}
		}
	}
	
	
	public function generateAddonCheckboxes($selected_values = array()) {
		global $db, $main;
		$result = $db->query("SELECT * FROM `<PRE>addons` WHERE status = ".ADDON_STATUS_ACTIVE);
		$html = '';
		if ($db->num_rows($result) > 0 ) {
			while($data = $db->fetch_array($result)) {
				$checked = false;
				if (isset($selected_values[$data['id']])) {
					$checked = true;
				}	
				$html .= $main->createCheckbox($data['name'], 'addon_'.$data['id'], $checked);					
			}
		}
		return $html;
	}
	
	public function generateAddonCheckboxesWithList($values, $selected_values = array(), $billing_id = null) {
		global $db, $main;
		$html = '';
		foreach($values as $value ) {
				$checked = false;
				if (isset($selected_values[$value['id']])) {
					$checked = true;
				}	
				$html .= $main->createCheckbox($value['name'], 'addon_'.$value['id'], $checked);					
		}
	
		return $html;
	}
	
	
	public function generateAddonCheckboxesWithBilling($billing_id, $package_id, $selected_values = array(), $show_price = false) {
		global $db, $main,$currency;
		$values = $this->getAllAddonsByBillingCycleAndPackage($billing_id, $package_id);
			
		$return_value = array();
		$html = '';		
		foreach($values as $value ) {
				$checked = false;
				if (isset($selected_values[$value['id']])) {
					$checked = true;					
				}	
				$html .= $main->createCheckbox($value['name'].' - '.$currency->toCurrency($value['amount']), 'addon_'.$value['id'], $checked);					
		}
		//$return_value= array('html'=> $html, 'total' => $total);
		return $html;
	}
	

	
	
	
	public function getAllAddons($status = ADDON_STATUS_ACTIVE) {
		global $db, $main;
		if (!in_array($status, array(ADDON_STATUS_ACTIVE, ADDON_STATUS_INACTIVE))) {
			$status = ADDON_STATUS_ACTIVE;
		}
		$result = $db->query("SELECT * FROM `<PRE>addons` WHERE status = ".$status);
		$addon_list = array();				
		if($db->num_rows($result) > 0) {
			while($data = $db->fetch_array($result)) {		
				$addon_list[$data['id']] = $data;
			}								
		}
		return $addon_list; 	
	}
	
	public function getAddonByBillingCycle($addon_id, $billing_id) {
		global $db;
		$addon_list = array();		
		if (!empty($billing_id)) {		
			$sql = "SELECT a.id, a.name, amount, bc.name  as billing_name  FROM `<PRE>addons` a INNER JOIN `<PRE>billing_products` b ON (a.id = b.product_id) INNER JOIN `<PRE>billing_cycles` bc
					ON (bc.id = b.billing_id) WHERE bc.id = {$billing_id} AND a.id = {$addon_id}  AND b.type= '".BILLING_TYPE_ADDON."'";		
			$addons_billing = $db->query($sql);
			$addon_list = array();
			while($data = $db->fetch_array($addons_billing)) {
				$addon_list[$data['id']] = array('id'=>$data['id'],  'name' => $data['name'], 'amount'=>$data['amount']);									
			}
		}
		return $addon_list;
	}
	
	
	public function getAddonsByPackage($package_id) {
		global $db, $main;
		
		$result = $db->query("SELECT * FROM `<PRE>addons` a INNER JOIN  `<PRE>package_addons` pa ON (pa.addon_id = a.id) WHERE package_id = ".$package_id);
		$addon_list = array();				
		if($db->num_rows($result) > 0) {
			while($data = $db->fetch_array($result)) {		
				$addon_list[$data['id']] = $data;
			}								
		}		
		return $addon_list;
	}
	
	
	
	/*
	public function getAddonByBillingCycleByOrder($addon_id, $billing_id) {
		global $db;
		$addong_list = array();		
		if (!empty($billing_id)) {		
			$sql = "SELECT a.id, a.name, amount, bc.name  as billing_name  FROM `<PRE>addons` a INNER JOIN `<PRE>billing_products` b ON (a.id = b.product_id) INNER JOIN `<PRE>billing_cycles` bc
					ON (bc.id = b.billing_id) WHERE bc.id = {$billing_id} AND a.id = {$addon_id} ";
			$addons_billing = $db->query($sql);
			$addong_list = array();
			while($data = $db->fetch_array($addons_billing)) {
				$addong_list[$data['id']] = array('id'=>$data['id'],  'name' => $data['name'], 'amount'=>$data['amount']);									
			}
		}
		return $addong_list;
	}*/
	
	
	
}

?>