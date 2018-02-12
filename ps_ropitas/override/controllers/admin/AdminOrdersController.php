<?php
 /**
  * @property Order $object
  */
 class AdminOrdersController extends AdminOrdersControllerCore
 {
     public function __construct()
     {
         $this->bootstrap = true;
         $this->table = 'order';
         $this->className = 'Order';
         $this->lang = false;
         $this->addRowAction('view');
         $this->explicitSelect = true;
         $this->allow_export = true;
         $this->deleted = false;
         $this->context = Context::getContext();
 
         parent::__construct();

         $this->_select = '
        a.id_currency,
        a.id_order AS id_pdf,

        IF (c.id_customer = 26,
(SELECT ifnull(customer_message.message, message.message)
FROM '._DB_PREFIX_.'orders orders
LEFT JOIN '._DB_PREFIX_.'message message on message.id_order = orders.id_order
LEFT JOIN (
select customer_thread.id_order, max(customer_message.id_customer_message) as id_customer_message
FROM '._DB_PREFIX_.'customer_thread customer_thread
inner JOIN '._DB_PREFIX_.'customer_message customer_message on customer_message.id_customer_thread = customer_thread.id_customer_thread
group by customer_thread.id_order, customer_thread.id_customer_thread
) last_customer_message on last_customer_message.id_order = orders.id_order
LEFT JOIN '._DB_PREFIX_.'customer_message customer_message on customer_message.id_customer_message = last_customer_message.id_customer_message
WHERE orders.id_order = a.id_order LIMIT 0,1
)
        ,
CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`)
        )
        AS `customer`,
        osl.`name` AS `osname`,
        os.`color`,
        IF((SELECT so.id_order FROM `'._DB_PREFIX_.'orders` so WHERE so.id_customer = a.id_customer AND so.id_order < a.id_order LIMIT 1) > 0, 0, 1) as new,
        state.`name` as cname,
        IF(a.valid, 1, 0) badge_success';
 
         $this->_join = '
         LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`)
         LEFT JOIN `'._DB_PREFIX_.'address` address ON address.id_address = a.id_address_delivery
         LEFT JOIN `'._DB_PREFIX_.'state` state ON address.id_state = state.id_state
         LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = a.`current_state`)
         LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$this->context->language->id.')';
         $this->_orderBy = 'id_order';
         $this->_orderWay = 'DESC';
         $this->_use_found_rows = true;
 
         $statuses = OrderState::getOrderStates((int)$this->context->language->id);
         foreach ($statuses as $status) {
             $this->statuses_array[$status['id_order_state']] = $status['name'];
         }
 
         $this->fields_list = array(
             'id_order' => array(
                 'title' => $this->l('ID'),
                 'align' => 'text-center',
                 'class' => 'fixed-width-xs'
             ),
             'reference' => array(
                 'title' => $this->l('Reference')
             ),
             'new' => array(
                 'title' => $this->l('New client'),
                 'align' => 'text-center',
                 'type' => 'bool',
                 'tmpTableFilter' => true,
                 'orderby' => false,
                 'callback' => 'printNewCustomer'
             ),
             'customer' => array(
                 'title' => $this->l('Customer'),
                 'havingFilter' => true,
             ),
         );
 
         if (Configuration::get('PS_B2B_ENABLE')) {
             $this->fields_list = array_merge($this->fields_list, array(
                 'company' => array(
                     'title' => $this->l('Company'),
                     'filter_key' => 'c!company'
                 ),
             ));
         }
 
         $this->fields_list = array_merge($this->fields_list, array(
             'total_paid_tax_incl' => array(
                 'title' => $this->l('Total'),
                 'align' => 'text-right',
                 'type' => 'price',
                 'currency' => true,
                 'callback' => 'setOrderCurrency',
                 'badge_success' => true
             ),
             'payment' => array(
                 'title' => $this->l('Payment')
             ),
             'osname' => array(
                 'title' => $this->l('Status'),
                 'type' => 'select',
                 'color' => 'color',
                 'list' => $this->statuses_array,
                 'filter_key' => 'os!id_order_state',
                 'filter_type' => 'int',
                 'order_key' => 'osname'
             ),
             'date_add' => array(
                 'title' => $this->l('Date'),
                 'align' => 'text-right',
                 'type' => 'datetime',
                 'filter_key' => 'a!date_add'
             ),
             'id_pdf' => array(
                 'title' => $this->l('PDF'),
                 'align' => 'text-center',
                 'callback' => 'printPDFIcons',
                 'orderby' => false,
                 'search' => false,
                 'remove_onclick' => true
             )
         ));

        if (State::isCurrentlyUsed('state', true)) {
             $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT DISTINCT c.id_state, c.`name`
            FROM `'._DB_PREFIX_.'orders` o
            '.Shop::addSqlAssociation('orders', 'o').'
            INNER JOIN `'._DB_PREFIX_.'address` a ON a.id_address = o.id_address_delivery
            INNER JOIN `'._DB_PREFIX_.'state` c ON a.id_state = c.id_state
            ORDER BY c.name ASC');
 
             $country_array = array();
             foreach ($result as $row) {
                $country_array[$row['id_state']] = $row['name'];
             }
 
             $part1 = array_slice($this->fields_list, 0, 3);
             $part2 = array_slice($this->fields_list, 3);
             $part1['cname'] = array(
                 'title' => $this->l('Delivery'),
                 'type' => 'select',
                 'list' => $country_array,
                'filter_key' => 'state!id_state',
                 'filter_type' => 'int',
                 'order_key' => 'cname'
             );
             $this->fields_list = array_merge($part1, $part2);
         }
 
         $this->shopLinkType = false;
         $this->shopShareDatas = Shop::SHARE_ORDER;
 
         if (Tools::isSubmit('id_order')) {
             // Save context (in order to apply cart rule)
             $order = new Order((int)Tools::getValue('id_order'));
             $this->context->cart = new Cart($order->id_cart);
             $this->context->customer = new Customer($order->id_customer);
         }
 
         $this->bulk_actions = array(
             'updateOrderStatus' => array('text' => $this->l('Change Order Status'), 'icon' => 'icon-refresh')
         );
 
     }

    public function renderForm()
    {
        parent::renderForm();

        $tpl_path = '../../../../modules/ps_ropitas/views/templates/admin/orders/form.tpl';
        $this->content .= $this->createTemplate($tpl_path, $this->context->smarty)->fetch();
    }

     public function setMedia()
     {
         parent::setMedia();
 
         $this->addJS(_PS_MODULE_DIR_ .'ps_ropitas/views/js/vendor/alphanum.js');
 
         if ($this->tabAccess['edit'] == 1 && $this->display == 'view') {
             $this->addJS(_PS_MODULE_DIR_ .'ps_ropitas/views/js/orders.js');
         }
     }
}