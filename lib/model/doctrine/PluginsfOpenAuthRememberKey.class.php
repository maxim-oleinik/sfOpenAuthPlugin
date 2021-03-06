<?php

/**
 * PluginsfOpenAuthRememberKey
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class PluginsfOpenAuthRememberKey extends BasesfOpenAuthRememberKey
{
    /**
     * Создать ключ
     *
     * @return string
     */
    static public function generateRandomKey()
    {
        return base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
    }

}
