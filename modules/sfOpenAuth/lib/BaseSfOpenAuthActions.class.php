<?php

// Zend wanna die after redirect, we don't
Zend_OpenId::$exitOnRedirect = false;


/**
 * OpenAuth actions
 *
 * @author Maxim Oleinik <maxim.oleinik@gmail.com>
 */
class BaseSfOpenAuthActions extends sfActions
{
    /**
     * Make OpenID consumer
     *
     * @return Zend_OpenId_Consumer
     */
    private function _makeConsumer()
    {
        $storage = new Zend_OpenId_Consumer_Storage_File(sfConfig::get('sf_app_cache_dir')."/openid-discovery");
        $consumer = new Zend_OpenId_Consumer($storage);
        $consumer->setSession(new sfOpenAuthZendSession("sf_zend_openid"));
        return $consumer;
    }


    /**
     * Make Sreg object
     *
     * @return Zend_OpenId_Extension_Sreg
     */
    private function _makeSreg()
    {
        $fields = array(
            'fullname' => false,
            'email'    => false,
        );
        return new Zend_OpenId_Extension_Sreg($fields, null, 1.1);
    }


    /**
     * Auth form & redirect to OpenID provider
     */
    public function executeLogin(sfWebRequest $request)
    {
        if ($this->getUser()->isAuthenticated()) {
            $this->redirect(sfConfig::get('app_open_auth_redirect_signin'));
        }

        if ($request->isMethod('post') && $request->hasParameter('openid_identifier')) {

            $identity = $request->getPostParameter('openid_identifier');

            // TODO: use form instead
            $validator = new sfValidatorUrl(array('protocols' => array('http', 'https')));
            try {
                $identity = $validator->clean($request->getPostParameter('openid_identifier'));
            } catch (sfValidatorError $e) {
                $this->error = 'Некорректно указан идентификатор.';
                $this->getResponse()->setStatusCode(400);
                return sfView::SUCCESS;
            }
            $response = new sfOpenAuthZendResponse;
            $consumer = $this->_makeConsumer();
            $sreg     = $this->_makeSreg();

            $urlVerify = $this->getController()->genUrl('open_auth_verify', true);
            $urlTrust  = $this->getController()->genUrl('homepage', true);

            if (!$consumer->login($identity, $urlVerify, $urlTrust, $sreg, $response)) {

                // $consumer->getError();
                $this->error = 'Ошибка! Возможно указанный аккаунт не существует.';
                $this->getResponse()->setStatusCode(400);
                return sfView::SUCCESS;
            }

            // Get "Location" header from Zend
            foreach ($response->getHeaders() as $item) {
                $this->getResponse()->setHttpHeader($item['name'], $item['value'], $item['replace']);
            }

        // Show auth form
        } else {
            $this->getResponse()->setStatusCode(401);
        }
    }


    /**
     * Verify & authenticate
     */
    public function executeVerify($request)
    {
        if ($this->getUser()->isAuthenticated()) {
            $this->redirect(sfConfig::get('app_open_auth_redirect_signin'));
        }

        $consumer = $this->_makeConsumer();
        $sreg     = $this->_makeSreg();

        // &$uid (by link)
        if ($consumer->verify($request->getParameterHolder()->getAll(), $uid, $sreg)) {
            $this->verifiedCallback($uid, $sreg);
        } else {
            $this->unverifiedCallback($consumer);
        }
    }


    /**
     * Verification success: authenticate or create user
     *
     * @param  string                     $uid  - Identity
     * @param  Zend_OpenId_Extension_Sreg $sreg
     * @return void
     */
    public function verifiedCallback($uid, Zend_OpenId_Extension_Sreg $sreg)
    {
        $user = Doctrine::getTable('sfOpenAuthUser')->findOneBy('identity', $uid);

        if (!$user) {
            $user = new sfOpenAuthUser;
            $user->setIdentity($uid);

            $props = $sreg->getProperties();
            if (!empty($props['fullname'])) {
                $user->setName($props['fullname']);
            }
            if (!empty($props['email'])) {
                $user->setEmail($props['email']);
            }

        }
        $user->setDateTimeObject('last_login', new DateTime);
        $user->save();

        $authUser = $this->getUser();
        $authUser->signIn($user);

        // remember
        // remove old keys
        $ttl = sfConfig::get('app_open_auth_remember_ttl');
        $q = Doctrine::getTable('sfOpenAuthRememberKey')->clean($user, $ttl);

        // save key
        $rk = new sfOpenAuthRememberKey;
        $rk->setUser($user);
        $rk->setIpAddress($_SERVER['REMOTE_ADDR']);
        $rk->setRememberKey(sfOpenAuthRememberKey::generateRandomKey());
        $rk->save();

        // make key as a cookie
        $this->getResponse()->setCookie(sfConfig::get('app_open_auth_remember_cookie'), $rk->getRememberKey(), time() + $ttl);

        $this->dispatcher->notifyUntil(new sfEvent($this, 'app.auth.success'));

        $this->redirect(sfConfig::get('app_open_auth_redirect_signin'));
    }


    /**
     * Verify error
     *
     * @param  Zend_OpenId_Consumer $consumer
     * @return void
     */
    public function unverifiedCallback(Zend_OpenId_Consumer $consumer)
    {
        $message = 'Ошибка авторизации';
        if ('dev' == sfConfig::get('sf_environment')) {
            $message .= '<br />' . $consumer->getError();
        }

        $this->getUser()->setFlash('error', $message);

        $this->redirect(sfConfig::get('app_open_auth_redirect_signout'));
    }


    /**
     * Signout
     */
    public function executeLogout()
    {
        $this->getUser()->signOut();
        $this->getResponse()->setCookie(sfConfig::get('app_open_auth_remember_cookie'), '', time() - 1);

        $this->redirect(sfConfig::get('app_open_auth_redirect_signout'));
    }

}
