<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginSkeletongatewayCallback extends PluginCallback
{
    var $pluginFolderName = 'skeletongateway';  //replace 'skeletongateway' with the respective plugin folder name

    function processCallback()
    {
        $pluginName = $this->settings->get('plugin_'.$this->pluginFolderName.'_Plugin Name');
        
        if (!isset($GLOBALS['testing'])) {
            $testing = false;
        } else {
            $testing = $GLOBALS['testing'];
        }

        CE_Lib::log(4, "$pluginName callback invoked");

        $logOK = $this->_logCallback();

        if(!$logOK){
            return;
        }

        // Comfirm the callback before assuming anything
        //   Add here some validation code to confirm the callback is authentic
        // Comfirm the callback before assuming anything

        // From the gateway callback, get the following values if possible, to assign them later to the transaction
        $Amount      = 0.00;        //Amount of the transaction. Can also be the amount refunded
        $TransID     = "00000";     //Identifier of the transaction assigned by the gateway
        $Action      = "charge";    //Can be "charge" or "refund"
        $Last4       = "NA";        //The last 4 digits of the Credit Card Number, if any was used, or "NA" if nothing
        $TransStatus = "Completed"; //Transaction status. Must determine if "Completed", "Pending", "Failed", etc

        $errorCode        = '';     //If the gateway returns an error code, assign it here
        $errorDescription = '';     //If the gateway returns an error description, assign it here

        // Create Plugin class object to interact with CE.
        $cPlugin = new Plugin($tInvoiceID, $this->pluginFolderName, $this->user);

        //Add plugin details
        $cPlugin->setAmount($Amount);
        $cPlugin->setTransactionID($TransID);
        $cPlugin->setAction($Action);
        $cPlugin->setLast4($Last4);

        // Manage the payment
        switch($TransStatus) {
            case "Completed":
                if ($Action == "refund") {
                    $transaction = "$pluginName refund of $Amount was successfully processed. (OrderID:$TransID)";
                } else {
                    $transaction = "$pluginName payment of $Amount was accepted. (OrderID:$TransID)";
                }
                $cPlugin->PaymentAccepted($Amount,$transaction,$TransID, $testing);
                return array('AMOUNT' => $Amount);
            break;
            case "Pending":
                if ($Action == "refund") {
                    $transaction = "$pluginName refund of $Amount was marked 'pending' by $pluginName. (OrderID:$TransID)";
                } else {
                    $transaction = "$pluginName payment of $Amount was marked 'pending' by $pluginName. (OrderID:$TransID)";
                }
                $cPlugin->PaymentPending($transaction,$TransID);
            break;
            case "Failed":
                if ($Action == "refund") {
                    $transaction = "$pluginName refund of $Amount was rejected. (OrderID:$TransID)";
                } else {
                    $transaction = "$pluginName payment of $Amount was rejected. (OrderID:$TransID)";
                }
                $cPlugin->PaymentRejected($transaction);

                if ($errorDescription != '' || $errorCode != '') {
                    CE_Lib::log(4, "$pluginName callback returned an error. $errorDescription Code:$errorCode");
                    return "$errorDescription Code:$errorCode";
                }
            break;
        }
    }

    //return true if can add the event log
    //return false if can not add the event log
    function _logCallback()
    {
        //This code assumes the values were returned inside $_POST and already knowing the variable names

        //Analyze the callback response and:
        //- Search for the invoice id. If not present, there is an issue
        if(!isset($_POST["invoice_id"])){
            //- Search for the transaction type. If not present, there is an issue.
            //- Here you can also ignore some transaction types, if not intended for the payments
            if(!isset($_POST["txn_type"])
              || !in_array(
                $_POST["txn_type"],
                array(
                  'ignore_transaction_type_1',
                  'ignore_transaction_type_2',
                  'etc'
                )
              )
            ){
                require_once 'modules/admin/models/Error_EventLog.php';
                $errorLog = Error_EventLog::newInstance(
                    false, 
                    0,
                    0,

                    //The particular event type requires some additional definitions on other files
                    ERROR_EVENTLOG_SKELETON_CALLBACK,

                    NE_EVENTLOG_USER_SYSTEM,
                    serialize($this->_utf8EncodeCallback($_POST))
                );
                $errorLog->save();
            }

            return false;
        }

        // search the customer id based on the invoice id
        $tInvoiceID = $_POST["invoice_id"];

        $query = "SELECT `customerid` "
                ."FROM `invoice` "
                ."WHERE `id` = ? ";
        $result = $this->db->query($query, $tInvoiceID);
        list($customerid) = $result->fetch();

        $invoiceNotFound = false;

        if(!isset($customerid)){
            $invoiceNotFound = true;

            // search the customer id based on the email address if available
            $query = "SELECT `id` "
                    ."FROM `users` "
                    ."WHERE `email` = ? ";
            $result = $this->db->query($query, $_POST["payer_email"]);
            list($customerid) = $result->fetch();
        }

        if(!isset($customerid) || $invoiceNotFound){
            require_once 'modules/admin/models/Error_EventLog.php';
            $errorLog = Error_EventLog::newInstance(
                false, 
                (isset($customerid))? $customerid : 0,
                $tInvoiceID,

                //The particular event type requires some additional definitions on other files
                ERROR_EVENTLOG_SKELETON_CALLBACK,

                NE_EVENTLOG_USER_SYSTEM,
                serialize($this->_utf8EncodeCallback($_POST))
            );
            $errorLog->save();
            return false;
        }else{
            require_once 'modules/billing/models/Invoice_EventLog.php';
            $invoiceLog = Invoice_EventLog::newInstance(
                false, 
                $customerid,
                $tInvoiceID,

                //The particular event type requires some additional definitions on other files
                INVOICE_EVENTLOG_SKELETON_CALLBACK,

                NE_EVENTLOG_USER_SYSTEM,
                serialize($this->_utf8EncodeCallback($_POST))
            );
            $invoiceLog->save();
            return true;
        }
    }

    //return array with the utf8 encoded values of the original array
    function _utf8EncodeCallback($callbackPost){
        if(is_array($callbackPost)){
            foreach($callbackPost as $postKey => $postValue){
                $callbackPost[$postKey] = utf8_encode($postValue);
            }
        }else{
            $callbackPost = array();
        }

        return $callbackPost;
    }
}

?>
