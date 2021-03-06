<?php 

class CPhpMailerLogRoute extends CEmailLogRoute
{

    private $_config = array(
        'From'       => 'no-reply@themedialab.co',
        'FromName'   => 'no-reply TheMediaLab adServer',
        // 'Host'       => "email-smtp.us-east-1.amazonaws.com",
        // 'SMTPAuth'   => true,
        // 'SMTPSecure' => "tls",
        // 'Port'       => 25,
        // 'Username'   => 'AKIAIQTRLJHEZETZDRSQ',
        // 'Password'   => 'Ag/ctgxpxYGrnQPxiahJiLNKldgoBJBr2M9mtf/Hz//F',
        'CharSet'    => "UTF-8",
    ); // Default values


    protected function sendEmail($email, $subject, $message, $isHTML=false)
    {
        $configuration = $this->getConfig();
        $mailer        = Yii::createComponent('application.extensions.mailer.EMailer');

        foreach ($configuration as $attribute => $value) {
            $mailer->$attribute = $value;
        }

        // $mailer->IsSMTP();
        $mailer->AddAddress($email);
        $mailer->Subject = $subject;
        $mailer->Body    = $message;
        $mailer->isHTML($isHTML);
        $mailer->Send();
    }

    /*
     * Public function for sendEmail
     */
    public function send($emails, $subject, $message, $isHTML=true)
    {
        switch ( $_SERVER['HTTP_HOST'] ) {
            // amazon prod
            case '54.88.85.63':
            case 'ec2-54-88-85-63.compute-1.amazonaws.com':
            case 'test.TheMediaLaberver.mobi':
            case 'app.TheMediaLaberver.mobi':
            case 'TheMediaLaberver.mobi':
                foreach ($emails as $email) {
                    $this->sendEmail($email, $subject, $message, $isHTML);
                }
            break;
        }

    }


     /**
     * Get the configurations
     *
     * @return array configurations for the send mechanism.
     */
    public function getConfig()
    {
        return $this->_config;
    }

 
    /**
     * Set all configurations inserted
     *
     * @param array() $value list of configurations to the PHPMailer send mechanism.
     *
     * @return void
     */
    public function setConfig($value)
    {
        $this->_config = array_merge($this->_config, $value);
    }

}

?>