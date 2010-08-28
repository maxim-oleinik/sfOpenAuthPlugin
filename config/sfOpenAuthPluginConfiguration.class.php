<?php


/**
 * sfOpenAuthPlugin configuration class
 *
 * @author Maxim Oleinik <maxim.oleinik@gmail.com>
 */
class sfOpenAuthPluginConfiguration extends sfPluginConfiguration
{
    /**
     * @see sfPluginConfiguration
     */
    public function initialize()
    {
        if (sfConfig::get('app_open_auth_routes_register') && in_array('sfOpenAuth', sfConfig::get('sf_enabled_modules', array()))) {
            $this->dispatcher->connect('routing.load_configuration', array($this, 'listenToRoutingLoadConfigurationEvent'));
        }
    }


    public function listenToRoutingLoadConfigurationEvent(sfEvent $event)
    {
        $r = $event->getSubject();

        // preprend our routes
        $r->prependRoute('open_auth_login',  new sfRoute('/login', array('module' => 'sfOpenAuth', 'action' => 'login')));
        $r->prependRoute('open_auth_logout', new sfRoute('/logout', array('module' => 'sfOpenAuth', 'action' => 'logout')));
        $r->prependRoute('open_auth_verify', new sfRoute('/auth/openid/verify', array('module' => 'sfOpenAuth', 'action' => 'verify')));
    }

}
