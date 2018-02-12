<?php
class Ps_Ropitas extends Module
{
	public function __construct()
	{
		$this->name = 'ps_ropitas';
		$this->version = '1.0.0';
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
			!Tools::copy(_PS_MODULE_DIR_.'/ps_ropitas/lib/service-worker.js', _PS_ROOT_DIR_.'/js/service-worker.js')
			||
			!Tools::copy(_PS_MODULE_DIR_.'/ps_ropitas/lib/opensearch.xml', _PS_ROOT_DIR_.'/opensearch.xml')
			||
			!Tools::copy(_PS_MODULE_DIR_.'/ps_ropitas/views/img/favicons/favicon.ico', _PS_ROOT_DIR_.'/favicon.ico')
			||
	        !$this->registerHook('header')
			) {
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
}
