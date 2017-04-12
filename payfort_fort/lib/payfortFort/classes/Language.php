<?php

class Payfort_Fort_Language extends Payfort_Fort_Super
{
    
    public static function __($input, $args = array(), $domain = 'payfort_fort')
    {        
        return __($input, $domain);
    }

    public static function getCurrentLanguageCode() 
    {
        return strtolower(substr(get_locale(), 0, 2));
    }
}

?>