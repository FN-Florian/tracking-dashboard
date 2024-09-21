<?php

class mailClass{

    public function SendMail($to, $subject, $message)
    {
        global $E_SMTP_Provider;

        if($E_SMTP_Provider == "sendgrid")
        {
            $result = $this->SendWithSendgrid($to, $subject, $message);
            var_dump($result);
        }
        else
        {
            return false;
        }
    }

    public function SendInviteMail($to, $inviteCode)
    {
        global $ProjectName;
        global $E_PageURL;

        if (substr($E_PageURL, -1) == "/") {
            $E_PageURL = substr($E_PageURL, 0, -1);
        }

        $subject = "Einladung zu ".$ProjectName;
        $message = "Du wurdest zu ".$ProjectName." eingeladen. Klicke auf den folgenden Link um dich zu registrieren: ".$E_PageURL."/".$inviteCode."/activation.html";

        $this->SendMail($to, $subject, $message);

        return true;        
    }

    private function SendWithSendgrid($to, $subject, $message)
    {
        // Sendgrid API
        global $E_Sendgrid_API_KEY;
        global $E_Mail_From;
        global $ProjectName;

        $email = new \SendGrid\Mail\Mail(); 
        $email->setFrom($E_Mail_From, $ProjectName);
        $email->setSubject($subject);
        $email->addTo($to);
        $email->addContent("text/html", $message);
        // click tracking disabled 
        $email->setClickTracking(false);
        $email->setOpenTracking(false);
        
        $sendgrid = new \SendGrid($E_Sendgrid_API_KEY);
        try {
            $response = $sendgrid->send($email);
            //echo 'Email sent successfully! Status Code: ' . $response->statusCode();
        } catch (Exception $e) {
            //echo 'Caught exception: '. $e->getMessage() ."\n";
        }
    }


}

?>