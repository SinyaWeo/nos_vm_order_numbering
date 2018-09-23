<?php
/*------------------------------------------------------------------------
# NOS Human Readable Order Numbering for Virtuemart
# ------------------------------------------------------------------------
# author:    NOS - Not Ordinary Software
# copyright: Copyright (C) 2013 NOS - Not Ordinary Software
# @license:  http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website:   http://www.nosoftware.cz
# contributors:
#	     Erik van de Wiel - enrikorules@hotmail.com
-------------------------------------------------------------------------*/

defined('_JEXEC') or die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');

jimport('joomla.plugin.plugin');

class plgSystemNos_vm_order_numbering extends JPlugin
{

    function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
    }
    function plgVmOnUserOrder(&$_orderData)
    {

        $mainframe = JFactory::getApplication();
        // Check if we are in frontend
        if ($mainframe->isAdmin())
        {
            return false;
        }
        $document = JFactory::getDocument();
        $doctype = $document->getType();
        // Check if we serve HTML document
        if ($doctype !== 'html')
        {
            return false;
        }
        // Extract last order from database
        $db = JFactory::getDBO();
        $q = 'SELECT COUNT(1) FROM #__virtuemart_orders WHERE `virtuemart_vendor_id`="' . $_orderData->virtuemart_vendor_id . '"';
        $db->setQuery($q);
        $count = $db->loadResult();
        // Add offset
        $count = $count + (int)VM_ORDER_OFFSET;

        // Get config parameters
        $numberingPattern = $this
            ->params
            ->get('numbering_pattern', '0');
        $userDefinition = $this
            ->params
            ->get('user_defined', '');
        $countLen = strlen(strval($count));

        // Is it default pattern or user defined?
        switch ($numberingPattern)
        {
            case '0':
                // Create order number according to YYYYMMDDNNNN pattern
                if ($countLen < 4)
                {
                    $_orderData->order_number = date('Ymd') . substr('0000', $countLen - 4) . $count;
                }
                else
                {
                    $_orderData->order_number = date('Ymd') . $count;
                }
            break;
            case '1':
                // Create order number according to user defined pattern
                // Split usern pattern definition by groups of characters
                $userPattern = preg_split('/(?<=(.))(?!\\1)/', $userDefinition);
                $newOrderNumber = '';
                foreach ($userPattern as $particle)
                {
                    $particleLength = - (strlen($particle));
                    switch ($particle[0])
                    {
                        case 'Y':
                            // Year
                            $newOrderNumber .= substr(date('Y') , $particleLength);
                        break;
                        case 'M':
                            // Month
                            $newOrderNumber .= date('m');
                        break;
                        case 'D':
                            // Day
                            $newOrderNumber .= date('d');
                        break;
                        case 'N':
                            // Order number
                            $zeroString = substr('00000000000000000000', $particleLength);
                            $zeroStringLength = strlen($zeroString);
                            if ($countLen < $zeroStringLength)
                            {
                                $newOrderNumber .= substr($zeroString, $countLen - $zeroStringLength) . $count;
                            }
                            else
                            {
                                $newOrderNumber .= $count;
                            }
                        break;
                            // Code added by Erik van de Wiel - enrikorules@hotmail.com
                            // begin
                            
                        case '-':
                            // Separator
                            $newOrderNumber .= "-";
                        break;
                            // end
                            
                    }
                }
                $_orderData->order_number = $newOrderNumber;
            break;
        }

        return $_orderData;

    }

}

?>
