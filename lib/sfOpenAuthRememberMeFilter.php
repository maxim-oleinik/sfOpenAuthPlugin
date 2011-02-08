<?php

/**
 * Remember me filter
 *
 * @author Maxim Oleinik <maxim.oleinik@gmail.com>
 */
class sfOpenAuthRememberMeFilter extends sfFilter
{
    /**
    * @see sfFilter
    */
    public function execute($filterChain)
    {
        $cookieName = sfConfig::get('app_open_auth_remember_cookie');

        if ($this->isFirstCall() && !$this->context->getUser()->isAuthenticated()
            && $cookie = $this->context->getRequest()->getCookie($cookieName)) {

            $rk = Doctrine::getTable('sfOpenAuthRememberKey')->findOneBy('remember_key', $cookie);

            if ($rk) {
                $this->context->getUser()->signIn($rk->getUser());
            }
        }

        $filterChain->execute();
    }
}
