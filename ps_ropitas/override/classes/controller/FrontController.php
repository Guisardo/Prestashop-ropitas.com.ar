<?php

class FrontController extends FrontControllerCore
{
    /**
     * Renders and adds color list HTML for each product in a list
     *
     * @param array $products
     */
    public function addColorsToProductList(&$products)
    {
        if (!is_array($products) || !count($products) || !file_exists(_PS_THEME_DIR_.'product-list-colors.tpl')) {
           return;
        }
 
        $products_need_cache = array();
        foreach ($products as &$product) {
          $products_need_cache[] = (int)$product['id_product'];
        }
 
        unset($product);
 
        $colors = false;
        if (count($products_need_cache)) {
           $colors = Product::getAttributesColorList($products_need_cache);
        }
 
        Tools::enableCache();
        foreach ($products as &$product) {
           $tpl = $this->context->smarty->createTemplate(_PS_THEME_DIR_.'product-list-colors.tpl', $this->getColorsListCacheId($product['id_product']));
           if (isset($colors[$product['id_product']])) {
              $tpl->assign(array(
                 'id_product'  => $product['id_product'],
                 'colors_list' => $colors[$product['id_product']],
                 'link'       => Context::getContext()->link,
                 'img_col_dir' => _THEME_COL_DIR_,
                 'col_img_dir' => _PS_COL_IMG_DIR_
              ));
           }
 
           if (!in_array($product['id_product'], $products_need_cache) || isset($colors[$product['id_product']])) {
              $product['color_list'] = $tpl->fetch(_PS_THEME_DIR_.'product-list-colors.tpl', $this->getColorsListCacheId($product['id_product']));
           } else {
              $product['color_list'] = '';
           }
        }
        Tools::restoreCacheSettings();
    }
}