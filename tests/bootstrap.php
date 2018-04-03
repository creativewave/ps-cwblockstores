<?php

define('_PS_ROOT_DIR_', dirname(__DIR__));

require_once 'cwblockstores.php';

class Module
{
    public function __construct()
    {
    }

    public function l($text)
    {
        return $text;
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function display($template_path, $template_name, $id_cache)
    {
        return '';
    }

    public function getCacheId($name = null)
    {
        return $name ?? $this->name;
    }
}
