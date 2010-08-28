<?php

/**
 * Zend_OpenID пытается что-то писать в сессию
 * TODO: разобраться что и зачем он туда пишет
 */
class sfOpenAuthZendSession extends Zend_Session_Namespace
{
    private
        $user = null,
        $ns = '';


    public function __construct($ns)
    {
        $this->ns = $ns;
        $this->user = sfContext::getInstance()->getUser();
    }


    public function __set($name,$val)
    {
        $this->user->setAttribute($name, $val, $this->ns);
    }

    public function &__get($name)
    {
        if ($this->user->hasAttribute($name, $this->ns)) {
            return $this->user->getAttribute($name, null, $this->ns);
        }
        return $_SESSION[$this->ns]; // satisfy return by reference
    }

}
