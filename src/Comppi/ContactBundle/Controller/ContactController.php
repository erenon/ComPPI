<?php

namespace Comppi\ContactBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Comppi\ContactBundle\Entity\Contact;
//use Comppi\ContactBundle\Form\ContactType;

class ContactController extends Controller
{
    private $contact_mail_to = 'veres.v.daniel@gmail.com'; # feel free to move to config.yml
	
    public function contactAction(Request $request)
    {
        $contact = new Contact();
		$form = $this->createFormBuilder($contact)
			->add('name')
			->add('email', 'email', array('label' => 'E-mail'))
			->add('message', 'textarea')
			->getForm();

		if ($request->getMethod() == 'POST') {
			$form->bindRequest($request);
		
			if ($form->isValid()) {
				$message = \Swift_Message::newInstance()
					->setSubject("ComPPI Contact Enquiry From '".$form->get('name')->getData()."'")
					->setFrom($form->get('email')->getData())
					->setTo($this->contact_mail_to)
					->setBody($this->renderView('ContactBundle:Default:contactemail.txt.twig', array('posted_data' => $form->getData())));
				$this->get('mailer')->send($message);
				
				$this->get('session')->setFlash('contact-notice', 'Your contact enquiry was sent successfully. Thank you!');
				
				return $this->redirect($this->generateUrl('ContactBundle_contact'));
			}
		}
	
		return $this->render('ContactBundle:Default:contactform.html.twig', array(
			'form' => $form->createView(),
		));
    }
}
