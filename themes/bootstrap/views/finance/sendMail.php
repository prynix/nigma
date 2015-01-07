<?php 
/* @var $this FinanceController */
/* @var $model Finance */
$date    = date('Y-m-d H:i:s', strtotime('NOW'));
$status  = "Sent";
$comment = null;

$validation_token  = md5($date.$io_id);
$revenueValidation = new IosValidation;
$log               = new ValidationLog;
// if($revenueValidation->checkValidationOpportunities($io_id,$period))
// {
	if($revenueValidation->checkValidation($io_id,$period))
	{
		$ioValidation=$revenueValidation->loadByIo($io_id,$period);
		$ioValidation->attributes=array('status'=>$status);
		// $revenueValidation->attributes=array('ios_id'=>$io_id,'period'=>$period,'date'=>$date, 'status'=>$status, 'comment'=>$comment,'validation_token'=>$validation_token);
		if($ioValidation->save())
		{
			
			$body = '
					<span style="color:#000">
					  <p>Dear client:</p>
					  <p>Please check the statement of your account by following the link below. We will assume that you are in agreement with us on the statement unless you inform us to the contrary by latest '.date('M j, Y').'</p>
					  <p><a href="http://kickadserver.mobi/externalForms/revenueValidation/'.$ioValidation->validation_token.'">http://kickadserver.mobi/externalForms/revenueValidation/'.$ioValidation->validation_token.'</a></p>
					  <p>If you weren’t the right contact person to verify the invoice, we ask you to follow the link above and update the information. Do not reply to this email with additional information.</p>
					  <p>This process allows us to audit the invoice together beforehand and expedite any paperwork required and payment.</p>
					  <p>Thanks</p>
					</span>
					<hr style="border: none; border-bottom: 1px solid #999;"/>
					<span style="color:#666">
					  <p>Estimado cliente:</p>
					  <p>Por favor verificar el estado de su cuenta a través del link a continuación. Se considerara de acuerdo con el estado actual a menos que se nos notifique lo contrario a mas tardar el '.date('d-m-Y').'</p>
					  <p><a href="http://kickadserver.mobi/externalForms/revenueValidation/'.$ioValidation->validation_token.'">http://kickadserver.mobi/externalForms/revenueValidation/'.$ioValidation->validation_token.'</a></p>
					  <p>Si usted no fuese la persona indicada para hacer esta verificación, le solicitamos ingrese al link anterior y actualice los datos. No responda a este correo con información adicional.</p>
					  <p>Este proceso nos permite auditar en conjunto la facturación previo a realizar y agilizar en lo posible el intercambio de documentos y el pago.</p>
					  <p>Gracias</p> 
					  <p><img src="http://kickads.mobi/logo/logo_kickads_181x56.png"/></p>
					</span>
                	';
            $subject = 'KickAds - Statement of account as per '.date('M j, Y');

            $mail = new CPhpMailerLogRoute;
            $mail->send(array('christian.motta@kickads.mobi'), $subject, $body);


		    echo 'Io #'.$ioValidation->ios_id.' mail enviado';
			$log->loadLog($ioValidation->id,$status);
		}
		else 
		    print_r($ioValidation->getErrors());
	}
	else
		    echo 'Las operaciones aun no han sido validadas';		
// }
// else
// 	echo 'Las opperaciones aun no han sido validadas';
?>