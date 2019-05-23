<?php
 /**
  * @property Order $object
  */
 class AdminOrdersController extends AdminOrdersControllerCore
 {
     public function __construct()
     {
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
CONCAT(c.`firstname`, \' \', c.`lastname`)
        )
        AS `customer`,
        osl.`name` AS `osname`,
        os.`color`,
        IF((SELECT so.id_order FROM `'._DB_PREFIX_.'orders` so WHERE so.id_customer = a.id_customer AND so.id_order < a.id_order AND so.valid = 1 LIMIT 1) > 0, 0, 1) as new,
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
    public function renderList()
    {
        $this->addRowAction('ShippingTag');
        $this->addRowAction('WhatsApp');
        return parent::renderList();
    }
    public function displayShippingtagLink($token = null, $id)
    {
        $order = new Order(intval($id));
        $order_status = (int)$order->getCurrentState();

        if ($order->payment == 'MercadoLibre' ||
            in_array($order_status, array(
            Configuration::get("MERCADOPAGO_STATUS_1"),
            Configuration::get("MERCADOPAGO_STATUS_8"),
            Configuration::get("MERCADOPAGO_STATUS_9"),
            Configuration::get("MERCADOPAGO_STATUS_10")
            ))) {
            return '<a target="_blank" href="/index.php?fc=module&module=mercadopago&controller=shippingtag&id_order='.$id.'"><i class="icon-envelope"></i> '.$this->l('Etiqueta').'</a>';
        } else {
            return '<a target="_blank" href="/index.php?fc=module&module=shippinglabel&controller=generator&id_order='.$id.'"><i class="icon-envelope"></i> '.$this->l('Etiqueta').'</a>';
        }
    }    
    public function displayWhatsappLink($token = null, $id)
    {
        $order = new Order(intval($id));
        $address = new Address($order->id_address_invoice);

        $phone = (int)preg_replace('/\D+/', '', $address->phone_mobile);
        if ($phone == 0) {
            $phone = (int)preg_replace('/\D+/', '', $address->phone);
        }

        $msg = '';
        $order_status = (int)$order->getCurrentState();
        if ($order_status == Configuration::get("MERCADOPAGO_STATUS_12")) {
            $sql = 'SELECT * FROM '._DB_PREFIX_.'mercadopago_orders_initpoint
                WHERE cart_id = '.$order->id_cart;
            if ($row = Db::getInstance()->getRow($sql)){
                if ($row['init_point']) {
                    $mp_initpoint = $row['init_point'];
                    $customer = new Customer($order->id_customer);
                    $msg = sprintf($this->l('Hola %s!
Mi nombre es Violeta. Te molesto para consultarte por el estado de tu pedido %s de Gamisé que armaste en www.ropitas.com.ar .
Veo que comenzaste con el armado del carrito de compras pero quedo en medio del proceso de pago y quedaron reservados los artículos.
Si querés continuar la compra podés aborarla entrando en %s , de lo contrario, avisame si querés que cancele el pedido.
Quedo atenta a tus consultas!!'), $customer->firstname, $order->reference, $mp_initpoint);
                }
            }
        }
        if ($order_status == Configuration::get("MERCADOPAGO_STATUS_7")) {
            $customer = new Customer($order->id_customer);
            $msg = sprintf($this->l('Hola %s!
Mi nombre es Violeta.
Te quería consultar si seguís interesada en concretar la compra del pedido %s de Gamisé que armaste en www.ropitas.com.ar o preferís que cancele la reserva de los artículos.
Necesitás que te espere unos días?
Saludos'), $customer->firstname, $order->reference);
        }
        if ($order_status == Configuration::get("MERCADOPAGO_STATUS_3")) {
            $sql = 'SELECT * FROM '._DB_PREFIX_.'mercadopago_orders_initpoint
                WHERE cart_id = '.$order->id_cart;
            if ($row = Db::getInstance()->getRow($sql)){
                if ($row['init_point']) {
                    $mp_initpoint = $row['init_point'];

                    $customer = new Customer($order->id_customer);

                    $msg = sprintf($this->l('Hola %s!
Te molesto para avisarte que el sistema rechazó el medio de pago que elegiste para el pedido %s de Gamisé que armaste en www.ropitas.com.ar .
Si te gustaría abonar por otro medio podes hacerlo entrando en %s
O preferís que cancelemos la reserva?
Quedo atenta a tus consultas!!'), $customer->firstname, $order->reference, $mp_initpoint);
                }
            }
        }
        $msg = str_replace('%26%23039%3B', "'", rawurlencode($msg));
        
        if ($phone == 0) {
            return false;
        } else {
            if (preg_match('/^15/', $phone) && in_array($address->id_state, array(99, 103))) {
                $phone = $phone - 1500000000 + 1100000000;
            }
            $phone = preg_replace('/^549/', '', $phone);
            $wpPrefix = 'web';
            $useragent=$_SERVER['HTTP_USER_AGENT'];
            if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
                $wpPrefix = 'api';
            }

            return '<a target="_blank" href="https://'.$wpPrefix.'.whatsapp.com/send?phone=549'.$phone.'&text='.$msg.'"><i class="icon-whatsapp"></i> WhatsApp</a>';
        }
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
