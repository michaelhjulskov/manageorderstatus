<?php
/*
// examlpe : how to call function
Manageorderstatus::updateOrderState($id_order, $id_order_state, $id_employee);

// examlpe : how to check whether this module is installed
... xxx ...
*/

class Manageorderstatus extends Module
{
	function __construct()
	{
		$this->name = 'manageorderstatus';
		$this->tab = 'others'
		$this->author = 'MichaelHjulskov';
		$this->email ="michael@hjulskov.dk";
		$this->need_instance = 0;               
		
		parent::__construct(); 

		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Manage order status v1.0');
		$this->description = $this->l('This module is simply a tool used by other modules, for updating order status and sending notification');
	}

	function install(){
		if (!parent::install())
			return false;
		return true;
	}
	
	public function uninstall(){      
		if(!parent::uninstall())
			return false;
		return true;
	}

	public function updateOrderState($id_order=false, $id_order_state=false, $id_employee=false)
	{
		// if (!$id_employee) $id_employee = (int)$this->context->employee->id;
		if (
			$id_order && is_int($id_order) && $id_order > 0 
			&& $id_order_state && is_int($id_order_state) && $id_order_state > 0
			&& $id_employee && is_int($id_employee) && $id_employee > 0
		){
			$order = new Order($id_order);
			if (Validate::isLoadedObject($order) && isset($order)){
				$order_state = new OrderState($id_order_state);
				if (Validate::isLoadedObject($order_state)){
					$employee = new Employee($id_employee);
					if (Validate::isLoadedObject($employee)){
						$current_order_state = $order->getCurrentOrderState();
						if ($current_order_state->id != $order_state->id){
							// Create new OrderHistory
							$history = new OrderHistory();
							$history->id_order = $order->id;
							$history->id_employee = $id_employee;

							$use_existings_payment = false;
							if (!$order->hasInvoice())
								$use_existings_payment = true;
							$history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);

							$carrier = new Carrier($order->id_carrier, $order->id_lang);
							$templateVars = array();
							if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number)
								$templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
							// Save all changes
							if ($history->addWithemail(true, $templateVars)){
								// synchronizes quantities if needed..
								if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')){
									foreach ($order->getProducts() as $product){
										if (StockAvailable::dependsOnStock($product['product_id']))
											StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
									}
								}
								return true;
								// no error message
							}
							return false; // TODO - maybe it should return true here, if state has been changed..
							//$this->errors[] = sprintf(Tools::displayError('An error occurred while changing order status on order id %d, or we were unable to send an email to the customer.'), $id_order);
						} else
							return false;
							//$this->errors[] = sprintf(Tools::displayError('The order id %d has already been assigned status %s.'), $id_order, $order_state->name);
					} else
						return false;
						//$this->errors[] = sprintf(Tools::displayError('id_employee %d doesnt exist'), $id_employee);
				} else 
					return false;
					//$this->errors[] = sprintf(Tools::displayError('The new order id_status %d is invalid.'), $id_order_state);
			} else 
				return false;
				//$this->errors[] = sprintf(Tools::displayError('The order id %d cannot be found within your database.'), $id_order);
		}
	}
}