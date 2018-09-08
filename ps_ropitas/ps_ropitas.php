<?php
class Ps_Ropitas extends Module
{
	public function __construct()
	{
		$this->name = 'ps_ropitas';
		$this->version = '1.2.0';
		$this->author = 'Guisardo';
		$this->need_instance = 0;

		parent::__construct();

		$this->tab = 'others';
		$this->displayName = $this->l('Sobrecargas de Bebé Gamisé');
		$this->description = $this->l('Customizaciones para ropitas.com.ar.');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
	}

	public function install()
	{
		if (!parent::install()) {
			return false;
		}

		if (
			!Tools::copy(_PS_MODULE_DIR_.'/ps_ropitas/views/js/service-worker.js', _PS_ROOT_DIR_.'/js/service-worker.js')
			||
			!Tools::copy(_PS_MODULE_DIR_.'/ps_ropitas/lib/opensearch.xml', _PS_ROOT_DIR_.'/opensearch.xml')
			||
			!Tools::copy(_PS_MODULE_DIR_.'/ps_ropitas/views/img/favicons/favicon.ico', _PS_ROOT_DIR_.'/favicon.ico')
			||
	        !$this->registerHook('header')
			||
	        !$this->registerHook('actionGetIDZoneByAddressID')
            ||
            !$this->createTables()
			) {
			return false;
		}
		
		return true;
	}

	private function createTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS "._DB_PREFIX_."cpa_cp_1974_shipping_zone (
	cod_postal INT NOT NULL,
	id_zone INT UNSIGNED NOT NULL,
	CONSTRAINT "._DB_PREFIX_."cpa_cp_1974_shipping_zone_"._DB_PREFIX_."zone_FK FOREIGN KEY (id_zone) REFERENCES "._DB_PREFIX_."zone(id_zone) ON DELETE CASCADE
)
ENGINE=InnoDB
DEFAULT CHARSET=latin1
COLLATE=latin1_general_ci;
";
        if (! Db::getInstance()->Execute($sql)) {
            return false;
        }
        
        return true;
	}

    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/global.css');
        $this->context->controller->addCSS($this->_path.'views/css/loading.css');
        $this->context->controller->addJS($this->_path.'views/js/sw-register.js');
        $this->context->controller->addJS($this->_path.'views/js/loading.js');

        return $this->display(__FILE__, 'views/templates/front/header.tpl');
    }

    public function hookActionGetIDZoneByAddressID($params) {
    	$result = false;
        $address = new Address($params["id_address"]);
        preg_match("/\d+/", $address->postcode, $postal_number);
        if ($postal_number) {
        	$cod_postal = $postal_number[0];
        	$sql = "SELECT id_zone FROM "._DB_PREFIX_."cpa_cp_1974_shipping_zone
WHERE cod_postal = ".$cod_postal."
";
	        $alt_zone = (int) Db::getInstance()->getValue($sql);
	        if ($alt_zone) {
	        	$result = (int) $alt_zone;
	        }
        }
        return $result;
    }
}
