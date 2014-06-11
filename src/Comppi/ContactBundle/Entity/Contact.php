<?php
// src/Comppi/ContactBundle/Entity/Contact.php

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\MinLength;
use Symfony\Component\Validator\Constraints\MaxLength;

namespace Comppi\ContactBundle\Entity;

class Contact
{
    protected $name;
    protected $email;
    protected $message;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }
	
//	public static function loadValidatorMetadata(ClassMetadata $metadata)
//    {
//		$metadata->addPropertyConstraint('name', new NotBlank());
//		$metadata->addPropertyConstraint('email', new Email(array(
//			'message' => 'Please provide a valid e-mail address.'
//		)));
//		$metadata->addPropertyConstraint('body', new MinLength(50));
//	}
}