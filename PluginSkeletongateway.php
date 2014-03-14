<?php
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'modules/billing/models/Currency.php';

/**
* @package Plugins
*/
class PluginSkeletongateway extends GatewayPlugin
{
    var $pluginFolderName = 'skeletongateway';  //replace 'skeletongateway' with the respective plugin folder name

    function getVariables()
    {
        /* Specification
            itemkey     - used to identify variable in your other functions
            type        - text,textarea,yesno,password,hidden,options
            description - description of the variable, displayed in ClientExec
            value       - default value
        */

        $variables = array (
            /*T*/"Plugin Name"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"How CE sees this plugin (not to be confused with the Signup Name)"/*/T*/,
                                "value"         =>/*T*/"Skeleton"/*/T*/
                                ),
            /*T*/"Demo Mode"/*/T*/ => array (
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES if you want to set this plugin in Demo mode for testing purposes."/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"Signup Name"/*/T*/ => array (
                                "type"          =>"text",
                                "description"   =>/*T*/"Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."/*/T*/,
                                "value"         =>"Credit Card, eCheck, or Skeleton"
                                ),
            /*T*/"Generate Invoices After Callback Notification"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"Select YES if you prefer CE to only generate invoices upon notification of payment via the callback supported by this processor.  Setting to NO will generate invoices normally but require you to manually mark them paid as you receive notification from processor."/*/T*/,
                                "value"         =>"1"
                                ),
            /*T*/"Invoice After Signup"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"Select YES if you want an invoice sent to the customer after signup is complete."/*/T*/,
                                "value"         =>"1"
                                ),
            /*T*/"Dummy Plugin"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"Auto Payment"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"Selecting YES allows admins to process payments automatically through this payment processor without requiring the customer intervention. Mainly used when Accept CC Number."/*/T*/,
                                "value"         =>"1"
                                ),

            /* CREDIT CARD CONFIGURATION */
            /*T*/"Accept CC Number"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"/*/T*/,
                                "value"         =>"1"
                                ),
            /*T*/"Visa"/*/T*/ => array (
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES to allow Visa card acceptance with this plugin.  No will prevent this card type."/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"MasterCard"/*/T*/ => array (
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES to allow MasterCard acceptance with this plugin. No will prevent this card type."/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"AmericanExpress"/*/T*/ => array (
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES to allow American Express card acceptance with this plugin. No will prevent this card type."/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"Discover"/*/T*/ => array (
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES to allow Discover card acceptance with this plugin. No will prevent this card type."/*/T*/,
                                "value"         =>"0"
                                ),
            /*T*/"LaserCard"/*/T*/ => array (
                                "type"          =>"yesno",
                                "description"   =>/*T*/"Select YES to allow LaserCard card acceptance with this plugin. No will prevent this card type."/*/T*/,
                                "value"         =>'0'
                                ),
            /*T*/"Check CVV2"/*/T*/ => array (
                                "type"          =>"hidden",
                                "description"   =>/*T*/"Select YES if you want to accept CVV2 for this plugin."/*/T*/,
                                "value"         =>"1"
                                ),
            /* CREDIT CARD CONFIGURATION */

            /* EXAMPLE OF A VARIABLE OF TYPE "OPTIONS" */
//          /*T*/"variable name"/*/T*/=> array(
//                              "type"          => "options",
//                              "description"   => /*T*/"description of the variable, displayed in ClientExec"/*/T*/,
//                              "options"       => array(0 => /*T*/"option 1"/*/T*/,
//                                                       1 => /*T*/"option 2"/*/T*/,
//                                                       2 => /*T*/"option 3"/*/T*/
//                                                      ),
//                              ),
        );
        return $variables;
    }

    //Function used to provide customers with the ability to do One-Time Payments (single payments)
    //Plugin variables can be accessed via $params["plugin_[pluginfoldername]_[variable]"] (ex. $params["plugin_".$this->pluginFolderName."_Demo Mode"])
    function singlePayment($params, $test = false)
    {
        //Instantiate this class if you need to format the currency amount, etc
        $currency = new Currency($this->user);

        //Function needs to build the url to the payment processor, then redirect
        //You will probably pass this url to the gateway as a parameter for it to know where to send the response
        $stat_url = mb_substr($params['clientExecURL'],-1,1) == "//" ? $params['clientExecURL']."plugins/gateways/".$this->pluginFolderName."/callback.php" : $params['clientExecURL']."/plugins/gateways/".$this->pluginFolderName."/callback.php";

        //THIS PIECE OF CODE IS ONLY USEFUL WITH SINGLE PAYMENTS, TO KNOW WHERE TO REDIRECT THE CUSTOMER AFTER THE PAYMENT
        //Need to check to see if user is coming from signup
        //You will probably pass this urls to the gateway as parameters for it to know where to redirect the user after the payment
        if ($params['isSignup']==1) {
            $returnURL        = $params["clientExecURL"]."/order.php?step=5&pass=1";
            $returnURL_Cancel = $params["clientExecURL"]."/order.php?step=5&pass=0";
        }else {
            $returnURL        = $params["clientExecURL"];
            $returnURL_Cancel = $params["clientExecURL"];
        }
        //THIS PIECE OF CODE IS ONLY USEFUL WITH SINGLE PAYMENTS, TO KNOW WHERE TO REDIRECT THE CUSTOMER AFTER THE PAYMENT

        //Create and send the request to the gateway

        //In the other hand, if the plugin also supports Auto Payment and does not require a different process, the code can be focused on the autopayment function
//      return $this->autopayment($params, $test);
    }

    //Function used to provide admins with the ability to do Auto Payment (Mainly used to charge CC Number)
    //Implementation not required if plugin does not support Auto Payment
    //Plugin variables can be accessed via $params["plugin_[pluginfoldername]_[variable]"] (ex. $params["plugin_".$this->pluginFolderName."_Demo Mode"])
    function autopayment($params, $test = false)
    {
        //Instantiate this class if you need to format the currency amount, etc
        $currency = new Currency($this->user);

        //Function needs to build the url to the payment processor, then redirect
        //You will probably pass this url to the gateway as a parameter for it to know where to send the response
        $stat_url = mb_substr($params['clientExecURL'],-1,1) == "//" ? $params['clientExecURL']."plugins/gateways/".$this->pluginFolderName."/callback.php" : $params['clientExecURL']."/plugins/gateways/".$this->pluginFolderName."/callback.php";

        //Create and send the request to the gateway
    }

    //Function used to provide admins with the ability to do refunds
    //Implementation not required if plugin does not support refunds
    //Plugin variables can be accessed via $params["plugin_[pluginfoldername]_[variable]"] (ex. $params["plugin_".$this->pluginFolderName."_Demo Mode"])
    function credit($params, $test = false)
    {
        //Instantiate this class if you need to format the currency amount, etc
        $currency = new Currency($this->user);

        //Function needs to build the url to the payment processor, then redirect
        //You will probably pass this url to the gateway as a parameter for it to know where to send the response
        $stat_url = mb_substr($params['clientExecURL'],-1,1) == "//" ? $params['clientExecURL']."plugins/gateways/".$this->pluginFolderName."/callback.php" : $params['clientExecURL']."/plugins/gateways/".$this->pluginFolderName."/callback.php";

        //Create and send the request to the gateway
    }
}
?>
