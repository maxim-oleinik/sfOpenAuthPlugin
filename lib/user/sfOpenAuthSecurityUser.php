<?php

/**
 * sfOpenAuthSecurityUser
 *
 * @author Maxim Oleinik <maxim.oleinik@gmail.com>
 */
class sfOpenAuthSecurityUser extends sfBasicSecurityUser
{
    protected $_user;


    /**
     * Init
     */
    public function initialize(sfEventDispatcher $dispatcher, sfStorage $storage, $options = array())
    {
        parent::initialize($dispatcher, $storage, $options);

        // remove user if timeout
        if (!$this->isAuthenticated()) {
            $this->getAttributeHolder()->removeNamespace('open_auth');
            $this->_user = null;
        }
    }


    /**
     * Get user entity
     *
     * @return user entity
     */
    public function getUserRecord()
    {
        $id = $this->getAttribute('user_id', null, 'open_auth');
        if (!$this->_user && $id) {

            $this->_user = Doctrine::getTable('sfOpenAuthUser')->find($id);

            // the user does not exist anymore in the database
            if (!$this->_user || !$id) {
                $this->signOut();
                throw new sfException(__METHOD__.': The user does not exist anymore in the database.');
            }

        } else if (!$id) {
            throw new sfException(__METHOD__.': Do not try to get user entity if not authenticated.');
        }

        return $this->_user;
    }


    /**
     * Sigin
     *
     * @param  Docrtine_Record $user
     * @return void
     */
    public function signIn(sfOpenAuthUser $user)
    {
        if (!$user->getId()) {
            throw new Exception(__METHOD__.": Expected user exists");
        }
        $this->_user = $user;

        $this->setAttribute('user_id', $user->getId(), 'open_auth');
        $this->setAuthenticated(true);
        $this->clearCredentials();
    }


    /**
     * Signout
     *
     * @return void
     */
    public function signOut()
    {
        $this->getAttributeHolder()->removeNamespace('open_auth');

        $this->setAuthenticated(false);
        $this->clearCredentials();
        $this->_user = null;
    }

}
