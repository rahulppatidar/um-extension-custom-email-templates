<?php
/*
Plugin Name: Ultimate Member Extension - Custom Email Templates
Description: Adds custom email templates to Ultimate Member.
Version: 1.0
Author: Rahul Ppatidar
*/


if ( ! defined( 'ABSPATH' ) ) exit;


if ( !is_plugin_active( 'ultimate-member/ultimate-member.php' ) ) {
function um_custom_email_template_dependencies() {
                    echo '<div class="error"><p>' . sprintf( __( 'The <strong>%s</strong> extension requires the Ultimate Member plugin to be activated to work properly. You can download it <a href="https://wordpress.org/plugins/ultimate-member">here</a>', 'um_custom_email_template' ), um_custom_email_template_extension ) . '</p></div>';
                }

                add_action( 'admin_notices', 'um_custom_email_template_dependencies' );
                return;
} 




/***************** RP CUstom UM Email ****************/

function rp_is_custom_template($template){
    $arr= explode("_",$template);
    if($arr[0]=='customrp'){
        return true;
    }else{
        return false;
    }

}


add_filter( 'um_email_notifications', 'my_email_notifications', 10, 1 );
function my_email_notifications( $emails ) {
    // your code here

$rp_um_custom_email_templates=get_option( 'rp_um_custom_email_templates') ? 
get_option( 'rp_um_custom_email_templates') : false;

if($rp_um_custom_email_templates){


if(is_array($rp_um_custom_email_templates)){
    foreach ($rp_um_custom_email_templates as $key => $value) {
        $name= __(ucwords(str_replace('customrp',' ',str_replace('_', ' ', $value))),'um_custom_email_template');
        $emails[$value] = array(            
            'key'           => $value,
            'title'         => __( $name,'um_custom_email_template' ),
            'subject'       => 'My Email Subject {site_name}!',
            'body'          => 'My Email body',
            'description'   => __('Send a notification to user','um_custom_email_template'),
            'recipient'     => 'user', // set 'admin' for make administrator as recipient

            'default_active' => false // can be false for make disabled by default
            );


    }
}


}


return $emails;
}

add_filter( 'um_change_email_template_file', 'my_change_email_template_file', 10, 1 );
function my_change_email_template_file( $template, $template_name ) {
    
    
    if(rp_is_custom_template($template)){
        add_filter( 'um_admin_settings_email_section_fields', 'my_admin_settings_email_section', 10, 2 );
    }
    return $template;
}



function my_admin_settings_email_section( $settings, $email_key ) {
    
    
   
   $new_field2=array(
                    'id'       => $email_key . '_email_bc_admin',
                    'type'     => 'checkbox',
                    'label'    => __( 'BC Administrator','um_custom_email_template' ),
                     'conditional' => array( $email_key . '_on', '=', 1 ),
                    'tooltip' => __('Check for add in bcc administrator','um_custom_email_template'),
                    
                ); 

     $new_field1=array(
                    'id'       => $email_key . '_email_delete',
                    'type'     => 'hidden',
                    'label'    => __( '','um_custom_email_template' ),        
                    'class'		=> ' delete_btn ',
                    'value'		=> 'Delete Template'	
                );  



   // array_unshift($settings ,$new_field2);
    array_unshift($settings ,$new_field2);
    array_push($settings, $new_field1);

    return $settings;
}

/*********** test ************/

//Add bulk action on user.php
add_filter( 'um_admin_bulk_user_actions_hook', 'rp_admin_bulk_user_actions', 10, 1 );
function rp_admin_bulk_user_actions( $actions ) {

$rp_um_custom_email_templates=get_option( 'rp_um_custom_email_templates') ? 
get_option( 'rp_um_custom_email_templates') : false;

if($rp_um_custom_email_templates){
if(is_array($rp_um_custom_email_templates)){
    foreach ($rp_um_custom_email_templates as $key => $value) {

        if(um_get_option($value.'_on')){
            $name= __(ucwords(str_replace('customrp',' ',str_replace('_', ' ', $value))),'um_custom_email_template');
                $actions[$value] = array(
                        'label' => $name
                );
        }

        
    }
}
}           



return $actions;
}


//Action on admin action
add_action( 'um_admin_user_action_hook', 'rp_admin_user_action', 10, 1 );

function rp_admin_user_action( $bulk_action ) {
    global $ultimatemember;
    error_reporting(1);
    if(rp_is_custom_template($bulk_action)){
        $template=$bulk_action; 
        $subject=UM()->options()->get($template.'_sub');
        $template_on=UM()->options()->get($template.'_on');     
        $template_path=get_stylesheet_directory_uri().'/ultimate-member/email/';
        $users=$_REQUEST['users'];
        $flag=false;



        $email_name=um_get_option($template.'_email_name');
        $email_recipents=um_get_option($template.'_email_recipent');

        $user_recipent_role=get_users( array('role'=>$email_recipents,'role__not_in'=>'administrator') );

        if($template_on){
            foreach ($users as $user_id_or_email) {
                
                $mailed=UM_RP_Mail( $user_id_or_email, $subject, $template, $template_path);
                    
                    if($mailed){            
                        $flag=true;
                    }
            }
        }
        
        $rp_redirect_url=home_url().'/wp-admin/users.php';

        if($flag){      
            wp_safe_redirect( add_query_arg( array( 'email_send' => 'success' ), $rp_redirect_url) );   
            exit;
        }else{          
            wp_safe_redirect( add_query_arg( array( 'email_send' => 'failed' ), $rp_redirect_url) );    
            exit;
        }



    }
}





//Add message that email has been send success of faild
add_action( "admin_head","rp_redirect_param");
function rp_redirect_param(){
    if(isset($_REQUEST['email_send']) && $_REQUEST['email_send']=='success'){
        add_action( 'admin_notices', 'rp_admin_notice_mail_success' );  
    }elseif(isset($_REQUEST['email_send']) && $_REQUEST['email_send']=='failed'){
        add_action( 'admin_notices', 'rp_admin_notice_mail_failed' );       
    }


    if(isset($_GET['email'])){
?>
<style>
#rp_add_custom_template_modal_btn,#rp_delete_custom_template_modal_btn{
    display: none;
}
a#deleteBtnByKs {
    margin-left: 10px;
}
</style>
<script>
	jQuery(document).ready(function(){
		if (jQuery(".delete_btn ").length > 0) {
			jQuery(".delete_btn ").css('width','78px !important');
			jQuery(".delete_btn ").prop("readonly", true);
            
			<?php if (isset($_GET['email'])){ $delete=$_GET['email']; $delete=str_replace('customrp_', '', $delete); echo"jQuery('#rp_delete_template_name').val('$delete');"; } ?>
            var btndeleteks="<a class='button' id='deleteBtnByKs'>Delete</a>";
            //jQuery( "#submit" ).append(btndeleteks);
            jQuery(btndeleteks).insertAfter("#submit");
			jQuery("#deleteBtnByKs").click(function(){
				jQuery("#rp_delete_custom_template_modal_btn").click();

			})
			
		}
       
	});
	
		
</script>
<?php        
    }
}

function rp_admin_notice_mail_success()
{
 ?>
    <div class="updated  notice">
        <p><?php _e( 'Email Successfully send!', 'um_custom_email_template' ); ?></p>
    </div>
<?php
}
function rp_admin_notice_mail_failed()
{
 ?>
    <div class="error notice">
        <p><?php _e( 'There has been an error. Can not sent email!', 'um_custom_email_template' ); ?></p>
    </div>
<?php
}




function UM_RP_Mail( $user_id_or_email = 1, $subject_line = 'Email Subject', $template, $path = null, 
    $args = array() ) {
        
        if ( absint( $user_id_or_email ) ) {
            $user = get_userdata( $user_id_or_email );
            $email = $user->user_email;
            $display_nameks=$user->first_name ." ".$user->last_name  ;
        } else {
            $email = $user_id_or_email;
        }


        if (um_get_option('customrp_test_email_bc_admin')) {
            $headers = array(
            'From: '. um_get_option('mail_from') .' <'. um_get_option('mail_from_addr') .'>', 
            'BCC: '.um_get_option('admin_email'), 
            );
        }else{
            $headers = 'From: '. um_get_option('mail_from') .' <'. um_get_option('mail_from_addr') .'>' . "\r\n";
        }
        
      //  $headers = 'From: '. um_get_option('mail_from') .' <'. um_get_option('mail_from_addr') .'>' . "\r\n";
        // $headers = 'From: '. um_get_option('mail_from') .' <'. um_get_option('mail_from_addr') .'>' . "\r\n";
       //echo $subject_line;
       
       $subject_line=str_replace('{display_name}', $display_nameks, $subject_line);
     
        $attachments = null;
        
        if ( file_exists( get_stylesheet_directory() . '/ultimate-member/templates/email/' . get_locale() . '/' . $template . '.html' ) ) {
            $path_to_email = get_stylesheet_directory() . '/ultimate-member/templates/email/' . get_locale() . '/' . $template . '.html';
        } else if ( file_exists( get_stylesheet_directory() . '/ultimate-member/templates/email/' . $template . '.html' ) ) {
            $path_to_email = get_stylesheet_directory() . '/ultimate-member/templates/email/' . $template . '.html';
        } else if ( file_exists(  $path . $template . '.html' ) ){
            $path_to_email = $path . $template . '.html';
        }else {
            $path_to_email = $path . $template . '.php';
        }

        if ( um_get_option('email_html') ) {
            $message = file_get_contents( $path_to_email );
            add_filter( 'wp_mail_content_type', 'um_mail_content_type' );
        } else {
            $message = ( um_get_option('email-' . $template ) ) ? um_get_option('email-' . $template ) : 'Untitled';
        }
        $message = str_replace('{display_name}', $display_nameks, $message);
        $message = um_convert_tags( $message, $args );
        $subject_line=um_convert_tags( $subject_line, $args );
        if(wp_mail( $email, $subject_line, $message, $headers, $attachments )){
            return true;
        }else{
            return false;
        }

}



function input_check($value)
{
    $value=trim($value);
    $value=sanitize_text_field($value);
  
    return $value;
}

function prepare_input($value)
{
    $value=strtolower($value);
    $value=str_replace(' ', '_', $value);
  
    return $value;
}


function rp_delete_value_from_numeric_array($arr,$value){
     if(is_array($arr)){            
        unset($arr[$value]);     
        $arr=array_values($arr);
        return $arr;       
    }
}

function rp_delete_value_from_accos_array($arr,$value){
    if(is_array($arr)){       
          unset($arr[$value]);
           return $arr;       
    }
}

add_action( 'um_settings_page_before_email__content','rp_settings_before_email_tab'  );
function rp_settings_before_email_tab(){


if(isset($_POST['rp_new_template_save'])){
   
    if(isset($_POST['rp_new_template_name']) && $_POST['rp_new_template_name']!=''){
           
        $rp_new_template_name=prepare_input(input_check($_POST['rp_new_template_name']));
       
        $rp_new_template_name='customrp_'.$rp_new_template_name;
       
        $rp_um_custom_email_templates=get_option( 'rp_um_custom_email_templates') ?
        get_option( 'rp_um_custom_email_templates') : false;

       

        $flag=false;

        if($rp_um_custom_email_templates){
            if(is_array($rp_um_custom_email_templates)){
                array_push($rp_um_custom_email_templates,$rp_new_template_name);
                if(update_option( 'rp_um_custom_email_templates', $rp_um_custom_email_templates, ture )){
                $flag=true;
                }
            }           
            
        }else{
            if(update_option( 'rp_um_custom_email_templates', array($rp_new_template_name), ture )){
                $flag=true;
            }
        }

        if($flag){
            echo "<script>alert('Template Successfully added!');location.reload();</script>";
        }else{
            echo "<script>alert('Template didn't Add!');</script>";
        }

    }

}



if(isset($_POST['rp_delete_template_btn'])){

    if(isset($_POST['rp_delete_template_name']) && $_POST['rp_delete_template_name']!=''){
    
        $rp_delete_template_name=prepare_input(input_check($_POST['rp_delete_template_name']));

        $rp_delete_template_name='customrp_'.$rp_delete_template_name;
    
       if(rp_is_custom_template($rp_delete_template_name)){
          
        $rp_um_custom_email_templates=get_option( 'rp_um_custom_email_templates') ?
        get_option( 'rp_um_custom_email_templates') : false;
      
        

        $flag=false;

        if($rp_um_custom_email_templates){

            if(is_array($rp_um_custom_email_templates)){
                print_r($rp_um_custom_email_templates);
               $key_index=array_search($rp_delete_template_name, $rp_um_custom_email_templates);
               echo "Index:".$key_index;
               if($key_index!=false || $key_index==0){
                    $rp_um_custom_email_templates= rp_delete_value_from_numeric_array(
                    $rp_um_custom_email_templates, $key_index);
               
                    print_r($rp_um_custom_email_templates);
                    if(update_option( 'rp_um_custom_email_templates', $rp_um_custom_email_templates, ture )){
                    $flag=true;
               
                }
               }else{
                echo "<script>alert('This template not exists!');</script>";
               }
               
            }           
            
        }

        if($flag){
               $um_option=get_option('um_options');

                $um_option=rp_delete_value_from_accos_array($um_option,$rp_delete_template_name.'_on');
                $um_option=rp_delete_value_from_accos_array($um_option,$rp_delete_template_name.'_sub');
                $um_option=rp_delete_value_from_accos_array($um_option,$rp_delete_template_name.'_email_name');
                $um_option=rp_delete_value_from_accos_array($um_option,$rp_delete_template_name.'_email_recipent');

                update_option( 'um_options', $um_option );

            $adminurl=get_admin_url();
            $adminurl=$adminurl."admin.php?page=um_options&tab=email";

            echo "<script>alert('Template Successfully Deleted!'); window.location.href='$adminurl';</script>";

        }else{
            echo "<script>alert('Can't Delete Template!');</script>";
        }

    }else{
        echo "<script>alert('This is not custom template!');</script>";
    }

    }

}





?>


<button id="rp_delete_custom_template_modal_btn"  class="button" onclick="rpmodal(this)" 
modal='rpDeleteCustomEmailTemplateModal'><?php _e('Delete Template','um_custom_email_template')?></button>
<!-- The Modal -->
<div id="rpDeleteCustomEmailTemplateModal" class="rpmodal">
    
  <!-- Modal content -->
  <div class="rpmodal-content" style="text-align: center; width: 310px;">   
    <div class="modal-header delete_template">
        <span class="rpclose">&times;</span>
    <h2><?php _e('Delete Template','um_custom_email_template')?></h2>
    </div>
    <div class="modal-body">
        <form id='rp_delete_template_form' name='rp_delete_template_form' method="post" action="">
            <label for="rp_delete_template_name" style="font-weight: 600;vertical-align: baseline;"><?php _e('Name: ','um_custom_email_template')?></label>
            <input type="text" name="rp_delete_template_name" id="rp_delete_template_name">
            <input type="submit" class="button" name="rp_delete_template_btn" id="rp_delete_template_btn" value="Delete">
        </form>
    </div>
    
  </div>

</div>






<!-- Trigger/Open The Modal -->
<button id="rp_add_custom_template_modal_btn"  class="button button-primary" onclick="rpmodal(this)" 
modal='rpAddCustomEmailTemplateModal'><?php _e('Add Template','um_custom_email_template')?></button>

<!-- The Modal -->
<div id="rpAddCustomEmailTemplateModal" class="rpmodal">
    
  <!-- Modal content -->
  <div class="rpmodal-content" style="text-align: center; width: 310px;">   
    <div class="modal-header add_template">
        <span class="rpclose">&times;</span>
    <h2><?php _e('Add New Template','um_custom_email_template')?></h2>
    </div>
    <div class="modal-body">
        <form id='rp_new_template_form' name='rp_new_template_form' method="post" action="">
            <label for="rp_new_template_name" style="font-weight: 600;vertical-align: baseline;"><?php _e('Name: ','um_custom_email_template')?></label>
            <input type="text" name="rp_new_template_name" id="rp_new_template_name">
            <input type="submit" class="button" name="rp_new_template_save" id="rp_new_template_save" value="Submit">
        </form>
    </div>
    
  </div>

</div>

<style type="text/css">
    /* The Modal (background) */
.rpmodal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 999999; /* Sit on top */
    padding-top: 100px; /* Location of the box */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}

.modal-header.add_template {
    padding: 2px 16px;
    background-color: #5cb85c;
    color: white;
}
.modal-header.delete_template {
    padding: 2px 16px;
    background-color: red;
    color: white;
}

.modal-body {padding: 20px 16px;}

/* Modal Content/Box */
.rpmodal-content {
    position: relative;
    background-color: #fefefe;
    margin: auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
    -webkit-animation-name: animatetop;
    -webkit-animation-duration: 0.4s;
    animation-name: animatetop;
    animation-duration: 0.4s
}

/* The Close Button */
.rpclose {
    color: white;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.rpclose:hover,
.rpclose:focus {
    color: #000;
    text-decoration: none;
    cursor: pointer;
}

/* Add Animation */
@-webkit-keyframes animatetop {
    from {top:-300px; opacity:0} 
    to {top:0; opacity:1}
}

@keyframes animatetop {
    from {top:-300px; opacity:0}
    to {top:0; opacity:1}
}


@media (max-width: 782px){
    #rp_new_template_save{
        margin-top: 10px;
    }
}
</style>
<script type="text/javascript">
    function rpmodal(btn){

// Get the modal
var modal=document.getElementById(btn.getAttribute('modal'));

// Get the <span> element that closes the modal
var span = modal.getElementsByClassName("rpclose")[0];

// When the user clicks on the button, open the modal 

modal.style.display = "block";


// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

}
</script>
<?php
}

