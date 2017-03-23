<?php
// plugins/HelloWorldBundle/Model/ContactModel.php

namespace MauticPlugin\HelloWorldBundle\Model;

use Mautic\CoreBundle\Model\CommonModel;

class ContactModel extends CommonModel
{

    /**
     * Send contact email
     * 
     * @param array $data
     */
    public function sendContactEmail($data)
    {
        // Get mailer helper - pass the mautic.helper.mailer service as a dependency
        $mailer = $this->mailer;

        $mailer->message->addTo(
            $this->factory->getParameter('mailer_from_email')
        );

        $this->message->setFrom(
            array($data['email'] => $data['name'])
        );

        $mailer->message->setSubject($data['subject']);

        $mailer->message->setBody($data['message']);

        $mailer->send();
    }
}

