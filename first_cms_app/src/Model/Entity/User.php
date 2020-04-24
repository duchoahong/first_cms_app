<?php
namespace App\Model\Entity;

use Cake\Auth\DefaultPasswordHasher; // Add this line

use Cake\ORM\Entity;

class User extends Entity
{
    protected $_accessible = [
        'email' => true,
        'password' => true,
        'created' => true,
        'modified' => true,
        'articles' => true,
    ];

    protected $_hidden = [
        'password',
    ];

    // Add this method 
    protected function _setPassword($value)
    {
        if (strlen($value))
        {
            $hasher = new DefaultPasswordHasher();

            return $hasher->hash($value);
        }
    }
}
