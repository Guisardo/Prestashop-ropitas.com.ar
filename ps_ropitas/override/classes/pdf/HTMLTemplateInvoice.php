<?php
class HTMLTemplateInvoice extends HTMLTemplateInvoiceCore {
    /**
     * Compute layout elements size
     *
     * @param $params Array Layout elements
     *
     * @return Array Layout elements columns size
     */
    protected function computeLayout($params)
    {
        $layout = array(
           'reference' => array(
              'width' => 15,
           ),
           'product' => array(
              'width' => 48,
           ),
           'quantity' => array(
              'width' => 8,
           ),
           'tax_code' => array(
              'width' => 8,
           ),
           'unit_price_tax_excl' => array(
              'width' => 0,
           ),
           'total_tax_excl' => array(
              'width' => 0,
           )
        );
 
        if (isset($params['has_discount']) && $params['has_discount']) {
           $layout['before_discount'] = array('width' => 0);
           $layout['product']['width'] -= 7;
           $layout['reference']['width'] -= 3;
        }
 
        $total_width = 0;
        $free_columns_count = 0;
        foreach ($layout as $data) {
           if ($data['width'] === 0) {
              ++$free_columns_count;
           }
 
           $total_width += $data['width'];
        }
 
        $delta = 100 - $total_width;
 
        foreach ($layout as $row => $data) {
           if ($data['width'] === 0) {
              $layout[$row]['width'] = $delta / $free_columns_count;
           }
        }
 
        $layout['_colCount'] = count($layout);
 
        return $layout;
    }
}
