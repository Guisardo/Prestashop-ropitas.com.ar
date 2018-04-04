<?php
abstract class HTMLTemplate extends HTMLTemplateCore
{
    /**
     * If the template is not present in the theme directory, it will return the default template
     * in _PS_PDF_DIR_ directory
     *
     * @param $template_name
     *
     * @return string
     */
    protected function getTemplate($template_name)
    {
        $template = false;
        $default_template = rtrim(_PS_PDF_DIR_, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$template_name.'.tpl';
        $overridden_template = '../../../../modules/ps_ropitas/views/templates/admin/pdf/'.$template_name.'.tpl';
        if (file_exists($overridden_template)) {
            $template = $overridden_template;
        } elseif (file_exists($default_template)) {
            $template = $default_template;
        }

        return $template;
    }
}
