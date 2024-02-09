<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

/**
 * Created by PhpStorm.
 * User: Ingener401
 * Date: 26.06.2018
 * Time: 10:48
 */

namespace frontend\models;


class AccessCheck
{
    /* checkAccess - Функция проверки прав доступа*/
    public static function checkAccess($login, $accessId)
    {
        $user = User::findOne(['login'=>$login]);
        if($user){
            $userAccess = UserAccess::findOne(['user_id'=>$user->id, 'access_id'=>$accessId]);
            if($userAccess){
                return true;
            }
        }
        return false;
    }
}