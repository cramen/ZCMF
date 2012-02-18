<?php

class Admin_Z_UsersController extends Z_Admin_Controller_Datacontrol_Abstract
{
    public function addOverride($param)
    {
        if ($this->_getParam('action') == 'edit') {
            if (!$param['password']) unset ($param['password']); else
                $param['password'] = Z_User::_cryptPassword($param['password']);
        } elseif ($this->_getParam('action') == 'add')
        {
            $param['password'] = Z_User::_cryptPassword($param['password']);
        }
        return $param;
    }

    public function editCheck($param)
    {
        $user = new Z_User($param['login']);
        if ($user->getRole() == 'root' && Z_Auth::getInstance()->getUser()->getRole() != 'root') {
            Z_FlashMessenger::addMessage('Нельзя изменить суперпользователя!');
            return false;
        }
        return true;
    }

}

