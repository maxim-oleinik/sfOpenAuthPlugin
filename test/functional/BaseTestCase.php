<?php

PHPUnit_Util_Filter::addFileToFilter(__FILE__);

/**
 * Базовый тест для авторизации
 */
abstract class sfOpenAuthPlugin_Functional_Auth_BaseTestCase extends myFunctionalTestCase
{
    // OpenID (взял из тестов зенда)
    const ID       = "http://id.some-openid-provider.test/";
    const REAL_ID  = "http://real_id.some-openid-provider.test/";
    const SERVER   = "http://www.some-openid-provider.test/";
    const HANDLE   = "d41d8cd98f00b204e9800998ecf8427e";
    const MAC_FUNC = "sha1";
    const SECRET   = "4fa03202081808bd19f92b667a291873";


    protected $storage;


    /**
     * SetUp
     */
    final protected function _start()
    {
        $this->storage = new Zend_OpenId_Consumer_Storage_File(\sfConfig::get('sf_app_cache_dir')."/openid-discovery");
        $this->storage->delDiscoveryInfo(self::ID);
        $this->storage->delAssociation(self::SERVER);
    }


    /**
     * Подготовить набор параметров для verify
     *
     * @return array
     */
    protected function _makeVerifyParameters(array $extraParams = array())
    {
        $_SERVER['SCRIPT_URI'] = 'http://localhost/index.php/auth/openid/verify';

        $expiresIn = TIME + 600;
        $this->storage->addDiscoveryInfo(self::ID, self::REAL_ID, self::SERVER, 1.1, $expiresIn);
        $this->storage->addAssociation(self::SERVER, self::HANDLE, "sha1", $secret = pack("H*", "8382aea922560ece833ba55fa53b7a975f597370"), $expiresIn);

        $params = array(
            "openid_return_to" => $_SERVER['SCRIPT_URI'],
            "openid_assoc_handle" => self::HANDLE,
            "openid_claimed_id" => self::ID,
            "openid_identity" => self::REAL_ID,
            "openid_response_nonce" => "2007-08-14T12:52:33Z46c1a59124ffe",
            "openid_mode" => "id_res",
            "openid_signed" => "assoc_handle,return_to,claimed_id,identity,response_nonce,mode,signed",
        );
        $signed = array($params["openid_signed"]);
        if ($extraParams) {
            $params = array_merge($params, $extraParams);
            // TODO: Фуууууу
            $extraParams2 = explode(',', str_replace('_', '.', implode(',', array_keys($extraParams))));
            $signed = array_merge($signed, $extraParams2);
        }
        $params["openid_signed"] = str_replace('openid.', '', implode(',', $signed));


        // Подписать данные
        $data = '';
        foreach (explode(',', $params['openid_signed']) as $key) {
            $data .= $key . ':' . $params['openid_' . strtr($key,'.','_')] . "\n";
        }
        $params['openid_sig'] = base64_encode(\Zend_OpenId::hashHmac('sha1', $data, $secret));

        return $params;
    }


    /**
     * Запрос на авторизацию/регистрацию
     *
     * @param  string $name
     * @param  string $email
     */
    protected function _browserCallAuth($name = null, $email = null)
    {
        if ($name && $email) {
            $params = array(
                "openid_ns_sreg"       => "http://openid.net/extensions/sreg/1.1",
                "openid_sreg_fullname" => $name,
                "openid_sreg_email"    => $email,
            );
        } else {
            $params = array();
        }
        $params = $this->_makeVerifyParameters($params);

        return $this->browser
            ->getAndCheck('sfOpenAuth', 'verify', $this->generateUrl('open_auth_verify', $params), 302);
    }

}
