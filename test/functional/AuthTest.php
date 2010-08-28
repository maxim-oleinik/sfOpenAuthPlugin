<?php

require_once dirname(__FILE__).'/BaseTestCase.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__);


/**
 * Authentication
 */
class sfOpenAuthPlugin_Functional_Auth_AuthTest extends sfOpenAuthPlugin_Functional_Auth_BaseTestCase
{
    public function getSecureRoute() {}


    /**
     * OpenID: Discovery и редирект к провайдеру
     */
    public function testOpenIdDiscoveryRedirect()
    {
        $expiresIn = TIME + 600;
        $this->assertTrue($this->storage->addDiscoveryInfo(self::ID, self::REAL_ID, self::SERVER, 1.1, $expiresIn));
        $this->assertTrue($this->storage->addAssociation(self::SERVER, self::HANDLE, self::MAC_FUNC, self::SECRET, $expiresIn));

        $this->browser
            ->getAndCheck('sfOpenAuth', 'login', $this->generateUrl('open_auth_login'), 401)
            ->with('response')->begin()
                ->isValid(true)
                ->checkElement('#openid_form')
            ->end()
            ->with("user")->isAuthenticated(false);

        // Авторизуемся
        $this->browser
            ->click('#openid_submit', array(
                'action'            => 'verify',
                'openid_identifier' => self::ID,
            ))
            ->with('request')->checkModuleAction('sfOpenAuth', 'login')
            ->with('request')->isMethod('POST')
            ->with('response')->checkElement('#openid_error', false);

        $this->assertTrue(strpos($this->browser->getResponse()->getHttpHeader('Location'), self::SERVER) === 0);
    }


    /**
     * OpenID: Ошибка discovery
     */
    public function testOpenIdDiscoveryError()
    {
        $this->browser
            ->post($this->generateUrl('open_auth_login'), array(
                'action'            => 'verify',
                'openid_identifier' => self::ID,
            ))
            ->with('response')->begin()
                ->isStatusCode(400)
                ->checkElement('#openid_error', true)
            ->end();
    }


    /**
     * OpenID: Полная регистрация
     */
    public function testOpenIdRegisterFull()
    {
        // Авторизация/регистрация и редирект на список событий
        $this->_browserCallAuth($name = "My Full Name", $email = "test@example.org")
            ->with('response')->checkRedirect(302, $this->generateUrl(sfConfig::get('app_open_auth_redirect_signin')))
            ->with("user")->isAuthenticated(true)
            ->with('model')->check('sfOpenAuthUser', array(
                'identity'     => self::ID,
                'name'         => $name,
                'email'        => $email,
            ), 1)
            ->with('response')->setsCookie('me');
    }


    /**
     * OpenID: Неполная регистрация (без указания обязательных полей профиля)
     */
    public function testOpenIdRegisterWithoutProfile()
    {
        // Авторизация/регистрация и редирект на профиль
        $this->_browserCallAuth()
            ->with('response')->checkRedirect(302, $this->generateUrl(sfConfig::get('app_open_auth_redirect_signin')))
            ->with("user")->isAuthenticated(true)
            ->with('model')->check('sfOpenAuthUser', array(
                'identity' => self::ID,
                'name' => null,
                'email' => null,
            ), 1)
            ->with('response')->setsCookie('me');
    }


    /**
     * OpenID: Авторизация - пользователь существует
     */
    public function testOpenIdAuthenticateThenUserExists()
    {
        $user = $this->helper->makeUser(array('identity' => self::ID));

        // Авторизация и редирект на список событий
        $this->_browserCallAuth('name', 'test@example.org')
            ->with('response')->checkRedirect(302, $this->generateUrl(sfConfig::get('app_open_auth_redirect_signin')))
            ->with("user")->isAuthenticated(true)
            ->with('response')->setsCookie('me');

        // Новый пользователь не создавался
        $this->assertEquals($user->getId(), $this->browser->getUser()->getUserRecord()->getId());
    }


    /**
     * OpenID: Ошибка авторизации
     */
    public function testOpenIdVerifyError()
    {
        // Отправить запрос на авторизацию
        $this->browser
            ->getAndCheck('sfOpenAuth', 'verify', $this->generateUrl('open_auth_verify'), 302)
            ->with('response')->checkRedirect(302, $this->generateUrl('open_auth_login'))
            ->with("user")->isAuthenticated(false);

        $flash = $this->browser->getUser()->getFlash('error');
        $this->assertContains('Ошибка авторизации', $flash);
    }


    /**
     * Auth: Если авторизован, то перебросить на список встреч
     */
    public function testRedirectIfAuthenticated()
    {
        $this->authenticateUser();

        $plan = array(
            array($this->generateUrl('open_auth_login'), 'get'),
            array($this->generateUrl('open_auth_login'), 'post'),
            array($this->generateUrl('open_auth_verify'), 'get'),
        );

        foreach ($plan as $item) {
            list($url, $method) = $item;
            $this->browser
                ->call($url, $method)
                ->with('response')->checkRedirect(302, $this->generateUrl(sfConfig::get('app_open_auth_redirect_signin')));
        }
    }


    /**
     * Редирект на исходную страницу после полной регистрации
     */
    public function testRedirectToOriginPageAfterRegisterFull()
    {
        $this->browser
            ->get($this->generateUrl(sfConfig::get('app_open_auth_redirect_signout')))
            ->isForwardedTo('sfOpenAuth', 'login');

        // Отправить запрос на авторизацию
        $this->_browserCallAuth('name', 'test@example.org')
            ->with("user")->isAuthenticated(true)
            ->with('response')->isStatusCode(302);
    }


    /**
     * Выход
     */
    public function testLogout()
    {
        $this->authenticateUser();

        $this->browser
            ->get($this->generateUrl('open_auth_logout'))
            ->with('user')->isAuthenticated(false)
            ->with('response')->checkRedirect(302, $this->generateUrl('open_auth_login'))
            ->with('response')->setsCookie('me', '');
    }


    /**
     * Авторизация по remember me куке
     */
    public function testRememberMe()
    {
        $user = $this->helper->makeUser(array('identity' => self::ID));

        // Авторизовались
        $this->_browserCallAuth()
            ->with('response')->setsCookie('me')
            ->with('model')->check('sfOpenAuthRememberKey',
                array(
                    'user_id' => $user->getId(),
                ), 1, $found);
        $rk = $found[0];

        // Очистить сессию
        $this->browser->getContext()->getStorage()->clear();

        // Авторизуемся автоматом по куке
        // TODO: авто продление куки
        $this->browser
            ->get($this->generateUrl($this->getSecureRoute()))
            ->with('response')->isStatusCode(200);
    }
}
