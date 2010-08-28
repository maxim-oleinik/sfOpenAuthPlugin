<?php

/**
 * Заглушка для Response, которая отдается зенду, чтобы не отдавал заголовки напрямую
 */
class sfOpenAuthZendResponse extends Zend_Controller_Response_Abstract
{
    public function canSendHeaders($throw = false)
    {
        return true;
    }

    public function sendResponse()
    {
    }

}
