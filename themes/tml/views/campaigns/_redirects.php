<?php
/* @var $this CampaignsController
 * @var $model Campaigns 
 * @var $form CActiveForm 
 * @var $campaignName
 */
?>

<div class="modal-header">
    <a class="close" data-dismiss="modal">&times;</a>
    <h4>Redirects for campaign #<?php echo $model->id ?></h4>
</div>

<div class="modal-body">
    <?php

    $redirect_old = array(
        'cid' => $model->id,
        'nid' => $provider->id,
        );
    $redirect_new = array();

    if(stristr($provider->name, "Adwords")){
        $redirect_old['g_net'] = '{network}';
        $redirect_old['g_key'] = '{keyword}';
        $redirect_old['g_cre'] = '{creative}';
        $redirect_old['g_pla'] = '{placement}';
        $redirect_old['g_mty'] = '{matchtype}';

        $redirect_new['g_net'] = '{network}';
        $redirect_new['g_key'] = '{keyword}';
        $redirect_new['g_cre'] = '{creative}';
        $redirect_new['g_pla'] = '{placement}';
        $redirect_new['g_mty'] = '{matchtype}';
    }

    if( $provider->has_token ) {
        $redirect_old['ntoken'] = $provider->placeholder;
        $redirect_new['ntoken'] = $provider->placeholder;
    }

    $redirect_old_query = urldecode( http_build_query($redirect_old) );
    $redirect_new_query = urldecode( http_build_query($redirect_new) );

    // tittle
    echo '<p><strong>External Name: </strong>' . $campaignName . ' </p>';
    echo '<hr/>';
    /*
    // redirect old format
    echo '<p style="color:#AAA">';
    echo '<strong>'.$provider->name.' (old format): </strong>';
    echo 'http://tmlbox.co/clickslog?';
    echo $redirect_old_query;
    echo '</p>';
    */
    // redirect new format
    echo '<p>';
    echo '<strong>'.$provider->name.' Redirect: </strong>';
    echo 'http://tmlbox.co/clicklog/tracking/'.$model->id.'/';
    if($redirect_new) echo '?' . $redirect_new_query;
    echo '</p>';
    echo '<hr/>';

    // s2s callback
    echo '<p><strong>S2S: </strong>';
    echo 'http://tmlbox.co/convlog';
    echo '?tmltoken=&lt;'.$model->opportunities->server_to_server.'&gt;';
    echo '</p>';
    //<button type="button" class="btn btn-default btn-sm">copy</button> 
    ?>
</div>

<div class="modal-footer">
    Copy and paste the redirect URL into the traffic source.
</div>

