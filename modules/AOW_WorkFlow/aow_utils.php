<?php
/**
 * Advanced OpenWorkflow, Automating SugarCRM.
 * @package Advanced OpenWorkflow for SugarCRM
 * @copyright SalesAgility Ltd http://www.salesagility.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU AFFERO GENERAL PUBLIC LICENSE
 * along with this program; if not, see http://www.gnu.org/licenses
 * or write to the Free Software Foundation,Inc., 51 Franklin Street,
 * Fifth Floor, Boston, MA 02110-1301  USA
 *
 * @author SalesAgility <info@salesagility.com>
 */


function getModuleFields($module, $view='EditView',$value = '', $valid = array())
{
    global $app_strings, $beanList;

    $fields = array(''=>$app_strings['LBL_NONE']);
    $unset = array();

    if ($module != '') {
        if(isset($beanList[$module]) && $beanList[$module]){
            $mod = new $beanList[$module]();
            foreach($mod->field_defs as $name => $arr){
                if(ACLController::checkAccess($mod->module_dir, 'list', true)) {
                    if($arr['type'] != 'link' &&((!isset($arr['source']) || $arr['source'] != 'non-db') || ($arr['type'] == 'relate' && isset($arr['id_name']))) && (empty($valid) || in_array($arr['type'], $valid)) && $name != 'currency_name' && $name != 'currency_symbol'){
                        if(isset($arr['vname']) && $arr['vname'] != ''){
                            $fields[$name] = rtrim(translate($arr['vname'],$mod->module_dir), ':');
                        } else {
                            $fields[$name] = $name;
                        }
                        if($arr['type'] == 'relate' && isset($arr['id_name']) && $arr['id_name'] != ''){
                            $unset[] = $arr['id_name'];
                        }
                    }
                }
            } //End loop.

            foreach($unset as $name){
                if(isset($fields[$name])) unset( $fields[$name]);
            }

        }
    }
    if($view == 'JSON'){
        return json_encode($fields);
    }
    if($view == 'EditView'){
        return get_select_options_with_id($fields, $value);
    } else {
        return $fields[$value];
    }
}

function getRelModuleFields($module, $rel_field, $view='EditView',$value = ''){
    global $beanList;

    if($module == $rel_field){
        return getModuleFields($module, $view, $value);
    }

    $mod = new $beanList[$module]();
    $data = $mod->field_defs[$rel_field];

    if(isset($data['module']) && $data['module'] != ''){
        return getModuleFields($data['module'], $view, $value);
    }

}

function getRelatedModule($module, $rel_field){
    global $beanList;

    if($module == $rel_field){
        return $module;
    }

    $mod = new $beanList[$module]();

    if(isset($arr['module']) && $arr['module'] != '') {
        return $arr['module'];
    } else if($mod->load_relationship($rel_field)){
        return $mod->$rel_field->getRelatedModuleName();
    }

    return $module;

}

function getModuleTreeData($module){
    global $beanList, $app_list_strings;

    $sort_fields = array();
    $module_label = isset($app_list_strings['moduleList'][$module]) ? $app_list_strings['moduleList'][$module] : $module;
    $fields = array(
        $module =>  array('label' => $module_label,
                        'type' => 'module',
                        'module' => $module,
                        'module_label'=> $module_label)
    );

    if ($module != '') {
        if(isset($beanList[$module]) && $beanList[$module]){
            $mod = new $beanList[$module]();

            foreach($mod->get_linked_fields() as $name => $arr){
                if(isset($arr['module']) && $arr['module'] != '') {
                    $rel_module = $arr['module'];
                } else if($mod->load_relationship($name)){
                    $rel_module = $mod->$name->getRelatedModuleName();
                }

                $rel_module_label = isset($app_list_strings['moduleList'][$rel_module]) ? $app_list_strings['moduleList'][$rel_module] : $rel_module;
                if(isset($arr['vname']) && $arr['vname'] != '') {
                    $label = $rel_module_label . ' : ' . translate($arr['vname'], $mod->module_dir);
                    $module_label = trim(translate($arr['vname'],$mod->module_dir),':');
                }else {
                    $label = $rel_module_label . ' : '. $name;
                    $module_label = $name;
                }
                $sort_fields[$name] = array('label'=>$label,'type'=>'relationship','module' => $rel_module,'module_label'=>$module_label);
                if($arr['type'] == 'relate' && isset($arr['id_name']) && $arr['id_name'] != ''){
                    if(isset($fields[$arr['id_name']])){
                        unset( $fields[$arr['id_name']]);
                    }
                }
            } //End loop.
            uasort($sort_fields,function($a,$b){
                return strcmp($a['label'],$b['label']);
            });

            $fields = array_merge((array)$fields, (array)$sort_fields);
        }
    }

    return json_encode($fields);
}

function getModuleRelationships($module, $view='EditView',$value = '')
{
    global $beanList, $app_list_strings;

    $fields = array($module=>$app_list_strings['moduleList'][$module]);
    $sort_fields = array();
    $invalid_modules = array();

    if ($module != '') {
        if(isset($beanList[$module]) && $beanList[$module]){
            $mod = new $beanList[$module]();

            /*if($mod->is_AuditEnabled()){
                $fields['Audit'] = translate('LBL_AUDIT_TABLE','AOR_Fields');
            }*/
            foreach($mod->get_linked_fields() as $name => $arr){
                if(isset($arr['module']) && $arr['module'] != '') {
                    $rel_module = $arr['module'];
                } else if($mod->load_relationship($name)){
                    $rel_module = $mod->$name->getRelatedModuleName();
                }
                if(!in_array($rel_module,$invalid_modules)){
                    $relModuleName = isset($app_list_strings['moduleList'][$rel_module]) ? $app_list_strings['moduleList'][$rel_module] : $rel_module;
                    if(isset($arr['vname']) && $arr['vname'] != ''){
                        $sort_fields[$name] = $relModuleName.' : '.translate($arr['vname'],$mod->module_dir);
                    } else {
                        $sort_fields[$name] = $relModuleName.' : '. $name;
                    }
                    if($arr['type'] == 'relate' && isset($arr['id_name']) && $arr['id_name'] != ''){
                        if(isset($fields[$arr['id_name']])) unset( $fields[$arr['id_name']]);
                    }
                }
            } //End loop.
            array_multisort($sort_fields, SORT_ASC, $sort_fields);
            $fields = array_merge((array)$fields, (array)$sort_fields);
        }
    }
    if($view == 'EditView'){
        return get_select_options_with_id($fields, $value);
    } else {
        return $fields[$value];
    }
}

function getValidFieldsTypes($module, $field){
    global $beanFiles, $beanList;

    require_once($beanFiles[$beanList[$module]]);
    $focus = new $beanList[$module];
    $vardef = $focus->getFieldDefinition($field);

    switch($vardef['type']) {
        case 'double':
        case 'decimal':
        case 'float':
        case 'currency':
            $valid_type = array('double','decimal','float','currency');
            break;
        case 'uint':
        case 'ulong':
        case 'long':
        case 'short':
        case 'tinyint':
        case 'int':
            $valid_type = array('uint','ulong','long','short','tinyint','int');
            break;
        case 'date':
        case 'datetime':
        case 'datetimecombo':
            $valid_type = array('date','datetime', 'datetimecombo');
            break;
        case 'id':
        case 'relate':
        case 'link':
            $valid_type = array('relate', 'id');
            //if($vardef['module'] == 'Users') $valid_type = array();
            break;
        default:
            $valid_type = array();
            break;
    }

    return $valid_type;
}


function getModuleField($module, $fieldname, $aow_field, $view='EditView',$value = '', $alt_type = '', $currency_id = '', $params= array()){
    global $current_language, $app_strings, $app_list_strings, $current_user, $beanFiles, $beanList;

    // use the mod_strings for this module
    $mod_strings = return_module_language($current_language,$module);

    // set the filename for this control
    $file = create_cache_directory('modules/AOW_WorkFlow/') . $module . $view . $alt_type . $fieldname . '.tpl';

    $displayParams = array();

    if ( !is_file($file)
        || inDeveloperMode()
        || !empty($_SESSION['developerMode']) ) {

        if ( !isset($vardef) ) {
            require_once($beanFiles[$beanList[$module]]);
            $focus = new $beanList[$module];
            $vardef = $focus->getFieldDefinition($fieldname);
        }

        // Bug: check for AOR value SecurityGroups value missing
        if(stristr($fieldname, 'securitygroups') != false && empty($vardef)) {
            require_once($beanFiles[$beanList['SecurityGroups']]);
            $module = 'SecurityGroups';
            $focus = new $beanList[$module];
            $vardef = $focus->getFieldDefinition($fieldname);
        }


        //$displayParams['formName'] = 'EditView';

        // if this is the id relation field, then don't have a pop-up selector.
        if( $vardef['type'] == 'relate' && $vardef['id_name'] == $vardef['name']) {
            $vardef['type'] = 'varchar';
        }

        if(isset($vardef['precision'])) unset($vardef['precision']);

        //$vardef['precision'] = $locale->getPrecedentPreference('default_currency_significant_digits', $current_user);

        //TODO Fix datetimecomebo
        //temp work around
        if( $vardef['type'] == 'datetimecombo') {
            $vardef['type'] = 'datetime';
        }

        // trim down textbox display
        if( $vardef['type'] == 'text' ) {
            $vardef['rows'] = 2;
            $vardef['cols'] = 32;
        }

        // create the dropdowns for the parent type fields
        if ( $vardef['type'] == 'parent_type' ) {
            $vardef['type'] = 'enum';
        }

        if($vardef['type'] == 'link'){
            $vardef['type'] = 'relate';
            $vardef['rname'] = 'name';
            $vardef['id_name'] = $vardef['name'].'_id';
            if((!isset($vardef['module']) || $vardef['module'] == '') && $focus->load_relationship($vardef['name'])) {
                $relName = $vardef['name'];
                $vardef['module'] = $focus->$relName->getRelatedModuleName();
            }

        }

        //check for $alt_type
        if ( $alt_type != '' ) {
            $vardef['type'] = $alt_type;
        }

        // remove the special text entry field function 'getEmailAddressWidget'
        if ( isset($vardef['function'])
            && ( $vardef['function'] == 'getEmailAddressWidget'
                || $vardef['function']['name'] == 'getEmailAddressWidget' ) )
            unset($vardef['function']);

        if(isset($vardef['name']) && ($vardef['name'] == 'date_entered' || $vardef['name'] == 'date_modified')){
            $vardef['name'] = 'aow_temp_date';
        }

        // load SugarFieldHandler to render the field tpl file
        static $sfh;

        if(!isset($sfh)) {
            require_once('include/SugarFields/SugarFieldHandler.php');
            $sfh = new SugarFieldHandler();
        }

        $contents = $sfh->displaySmarty('fields', $vardef, $view, $displayParams);

        // Remove all the copyright comments
        $contents = preg_replace('/\{\*[^\}]*?\*\}/', '', $contents);

        if( $view == 'EditView' &&  ($vardef['type'] == 'relate' || $vardef['type'] == 'parent')){
            $contents = str_replace('"'.$vardef['id_name'].'"','{/literal}"{$fields.'.$vardef['name'].'.id_name}"{literal}', $contents);
            $contents = str_replace('"'.$vardef['name'].'"','{/literal}"{$fields.'.$vardef['name'].'.name}"{literal}', $contents);
        }

        // hack to disable one of the js calls in this control
        if ( isset($vardef['function']) && ( $vardef['function'] == 'getCurrencyDropDown' || $vardef['function']['name'] == 'getCurrencyDropDown' ) )
            $contents .= "{literal}<script>function CurrencyConvertAll() { return; }</script>{/literal}";

        // Save it to the cache file
        if($fh = @sugar_fopen($file, 'w')) {
            fputs($fh, $contents);
            fclose($fh);
        }
    }

    // Now render the template we received
    $ss = new Sugar_Smarty();

    // Create Smarty variables for the Calendar picker widget
    global $timedate;
    $time_format = $timedate->get_user_time_format();
    $date_format = $timedate->get_cal_date_format();
    $ss->assign('USER_DATEFORMAT', $timedate->get_user_date_format());
    $ss->assign('TIME_FORMAT', $time_format);
    $time_separator = ":";
    $match = array();
    if(preg_match('/\d+([^\d])\d+([^\d]*)/s', $time_format, $match)) {
        $time_separator = $match[1];
    }
    $t23 = strpos($time_format, '23') !== false ? '%H' : '%I';
    if(!isset($match[2]) || $match[2] == '') {
        $ss->assign('CALENDAR_FORMAT', $date_format . ' ' . $t23 . $time_separator . "%M");
    }
    else {
        $pm = $match[2] == "pm" ? "%P" : "%p";
        $ss->assign('CALENDAR_FORMAT', $date_format . ' ' . $t23 . $time_separator . "%M" . $pm);
    }

    $ss->assign('CALENDAR_FDOW', $current_user->get_first_day_of_week());

    // populate the fieldlist from the vardefs
    $fieldlist = array();
    if ( !isset($focus) || !($focus instanceof SugarBean) )
        require_once($beanFiles[$beanList[$module]]);
    $focus = new $beanList[$module];
    // create the dropdowns for the parent type fields
    $vardefFields = $focus->getFieldDefinitions();
    if (isset($vardefFields[$fieldname]['type']) && $vardefFields[$fieldname]['type'] == 'parent_type' ) {
        $focus->field_defs[$fieldname]['options'] = $focus->field_defs[$vardefFields[$fieldname]['group']]['options'];
    }
    foreach ( $vardefFields as $name => $properties ) {
        $fieldlist[$name] = $properties;
        // fill in enums
        if(isset($fieldlist[$name]['options']) && is_string($fieldlist[$name]['options']) && isset($app_list_strings[$fieldlist[$name]['options']]))
            $fieldlist[$name]['options'] = $app_list_strings[$fieldlist[$name]['options']];
        // Bug 32626: fall back on checking the mod_strings if not in the app_list_strings
        elseif(isset($fieldlist[$name]['options']) && is_string($fieldlist[$name]['options']) && isset($mod_strings[$fieldlist[$name]['options']]))
            $fieldlist[$name]['options'] = $mod_strings[$fieldlist[$name]['options']];
        // Bug 22730: make sure all enums have the ability to select blank as the default value.
        if(!isset($fieldlist[$name]['options']['']))
            $fieldlist[$name]['options'][''] = '';
    }

    // fill in function return values
    if ( !in_array($fieldname,array('email1','email2')) )
    {
        if (!empty($fieldlist[$fieldname]['function']['returns']) && $fieldlist[$fieldname]['function']['returns'] == 'html')
        {
            $function = $fieldlist[$fieldname]['function']['name'];
            // include various functions required in the various vardefs
            if ( isset($fieldlist[$fieldname]['function']['include']) && is_file($fieldlist[$fieldname]['function']['include']))
                require_once($fieldlist[$fieldname]['function']['include']);
            $_REQUEST[$fieldname] = $value;
            $value = $function($focus, $fieldname, $value, $view);

            $value = str_ireplace($fieldname, $aow_field, $value);
        }
    }

    if(isset($fieldlist[$fieldname]['type']) && $fieldlist[$fieldname]['type'] == 'link'){
        $fieldlist[$fieldname]['id_name'] = $fieldlist[$fieldname]['name'].'_id';

        if((!isset($fieldlist[$fieldname]['module']) || $fieldlist[$fieldname]['module'] == '') && $focus->load_relationship($fieldlist[$fieldname]['name'])) {
            $relName = $fieldlist[$fieldname]['name'];
            $fieldlist[$fieldname]['module'] = $focus->$relName->getRelatedModuleName();
        }
    }

    if(isset($fieldlist[$fieldname]['name']) && ($fieldlist[$fieldname]['name'] == 'date_entered' || $fieldlist[$fieldname]['name'] == 'date_modified')){
        $fieldlist[$fieldname]['name'] = 'aow_temp_date';
        $fieldlist['aow_temp_date'] = $fieldlist[$fieldname];
        $fieldname = 'aow_temp_date';
    }

    $quicksearch_js = '';
    if(isset( $fieldlist[$fieldname]['id_name'] ) && $fieldlist[$fieldname]['id_name'] != '' && $fieldlist[$fieldname]['id_name'] != $fieldlist[$fieldname]['name']){
        $rel_value = $value;

        require_once("include/TemplateHandler/TemplateHandler.php");
        $template_handler = new TemplateHandler();
        $quicksearch_js = $template_handler->createQuickSearchCode($fieldlist,$fieldlist,$view);
        $quicksearch_js = str_replace($fieldname, $aow_field.'_display', $quicksearch_js);
        $quicksearch_js = str_replace($fieldlist[$fieldname]['id_name'], $aow_field, $quicksearch_js);

        echo $quicksearch_js;

        if(isset($fieldlist[$fieldname]['module']) && $fieldlist[$fieldname]['module'] == 'Users'){
            $rel_value = get_assigned_user_name($value);
        } else if(isset($fieldlist[$fieldname]['module'])){
            require_once($beanFiles[$beanList[$fieldlist[$fieldname]['module']]]);
            $rel_focus = new $beanList[$fieldlist[$fieldname]['module']];
            $rel_focus->retrieve($value);
            if(isset($fieldlist[$fieldname]['rname']) && $fieldlist[$fieldname]['rname'] != ''){
                $relDisplayField = $fieldlist[$fieldname]['rname'];
            } else {
                $relDisplayField = 'name';
            }
            $rel_value = $rel_focus->$relDisplayField;
        }

        $fieldlist[$fieldlist[$fieldname]['id_name']]['value'] = $value;
        $fieldlist[$fieldname]['value'] = $rel_value;
        $fieldlist[$fieldname]['id_name'] = $aow_field;
        $fieldlist[$fieldlist[$fieldname]['id_name']]['name'] = $aow_field;
        $fieldlist[$fieldname]['name'] = $aow_field.'_display';
    } else if(isset( $fieldlist[$fieldname]['type'] ) && $view == 'DetailView' && ($fieldlist[$fieldname]['type'] == 'datetimecombo' || $fieldlist[$fieldname]['type'] == 'datetime' || $fieldlist[$fieldname]['type'] == 'date')){
        $value = $focus->convertField($value, $fieldlist[$fieldname]);
        if(!empty($params['date_format']) && isset($params['date_format'])){
            $convert_format = "Y-m-d H:i:s";
            if($fieldlist[$fieldname]['type'] == 'date') $convert_format = "Y-m-d";
            $fieldlist[$fieldname]['value'] = $timedate->to_display($value, $convert_format, $params['date_format']);
        }else{
            $fieldlist[$fieldname]['value'] = $timedate->to_display_date_time($value, true, true);
        }
        $fieldlist[$fieldname]['name'] = $aow_field;
    } else if(isset( $fieldlist[$fieldname]['type'] ) && ($fieldlist[$fieldname]['type'] == 'datetimecombo' || $fieldlist[$fieldname]['type'] == 'datetime' || $fieldlist[$fieldname]['type'] == 'date')){
        $value = $focus->convertField($value, $fieldlist[$fieldname]);
        $fieldlist[$fieldname]['value'] = $timedate->to_display_date($value);
        //$fieldlist[$fieldname]['value'] = $timedate->to_display_date_time($value, true, true);
        //$fieldlist[$fieldname]['value'] = $value;
        $fieldlist[$fieldname]['name'] = $aow_field;
    } else {
        $fieldlist[$fieldname]['value'] = $value;
        $fieldlist[$fieldname]['name'] = $aow_field;

    }

    if(isset($fieldlist[$fieldname]['type']) && $fieldlist[$fieldname]['type'] == 'currency' && $view != 'EditView'){
        static $sfh;

        if(!isset($sfh)) {
            require_once('include/SugarFields/SugarFieldHandler.php');
            $sfh = new SugarFieldHandler();
        }

        if($currency_id != '' && !stripos($fieldname, '_USD')){
            $userCurrencyId = $current_user->getPreference('currency');
            if($currency_id != $userCurrencyId){
                $currency = new Currency();
                $currency->retrieve($currency_id);
                $value = $currency->convertToDollar($value);
                $currency->retrieve($userCurrencyId);
                $value = $currency->convertFromDollar($value);
            }
        }

        $parentfieldlist[strtoupper($fieldname)] = $value;

        return($sfh->displaySmarty($parentfieldlist, $fieldlist[$fieldname], 'ListView', $displayParams));
    }

    if($fieldlist[$fieldname]['type'] == 'bool'){
        return $value;
    }

    $ss->assign("QS_JS", $quicksearch_js);
    $ss->assign("fields",$fieldlist);
    $ss->assign("form_name",$view);
    $ss->assign("bean",$focus);

    // add in any additional strings
    $ss->assign("MOD", $mod_strings);
    $ss->assign("APP", $app_strings);

    //$return = str_replace($fieldname,$ss->fetch($file));

    return $ss->fetch($file);
}



function getDateField($module, $aow_field, $view='EditView', $value, $field_option = true){
    global $app_list_strings;

    $value = json_decode(html_entity_decode_utf8($value), true);

    if(!file_exists('modules/AOBH_BusinessHours/AOBH_BusinessHours.php')) unset($app_list_strings['aow_date_type_list']['business_hours']);

    $field = '';

    if($view == 'EditView'){
        $field .= "<select type='text' name='$aow_field".'[0]'."' id='$aow_field".'[0]'."' title='' tabindex='116'>". getDateFields($module, $view, $value[0], $field_option) ."</select>&nbsp;&nbsp;";
        $field .= "<select type='text' name='$aow_field".'[1]'."' id='$aow_field".'[1]'."' onchange='date_field_change(\"$aow_field\")'  title='' tabindex='116'>". get_select_options_with_id($app_list_strings['aow_date_operator'], $value[1]) ."</select>&nbsp;";
        $display = 'none';
        if($value[1] == 'plus' || $value[1] == 'minus') $display = '';
        $field .= "<input  type='text' style='display:$display' name='$aow_field".'[2]'."' id='$aow_field".'[2]'."' title='' value='$value[2]' tabindex='116'>&nbsp;";
        $field .= "<select type='text' style='display:$display' name='$aow_field".'[3]'."' id='$aow_field".'[3]'."' title='' tabindex='116'>". get_select_options_with_id($app_list_strings['aow_date_type_list'], $value[3]) ."</select>";
    }
    else {
        $field = getDateFields($module, $view, $value[0], $field_option).' '.$app_list_strings['aow_date_operator'][$value[1]];
        if($value[1] == 'plus' || $value[1] == 'minus'){
            $field .= ' '.$value[2].' '.$app_list_strings['aow_date_type_list'][$value[3]];
        }
    }
    return $field;

}

function getDateFields($module, $view='EditView',$value = '', $field_option = true)
{
    global $beanList, $app_list_strings;

    $fields = $app_list_strings['aow_date_options'];

    if(!$field_option) unset($fields['field']);

    if ($module != '') {
        if(isset($beanList[$module]) && $beanList[$module]){
            $mod = new $beanList[$module]();
            foreach($mod->field_defs as $name => $arr){
                if($arr['type'] == 'date' || $arr['type'] == 'datetime' || $arr['type'] == 'datetimecombo'){
                    if(isset($arr['vname']) && $arr['vname'] != ''){
                        $fields[$name] = translate($arr['vname'],$mod->module_dir);
                    } else {
                        $fields[$name] = $name;
                    }
                }
            } //End loop.

        }
    }
    if($view == 'EditView'){
        return get_select_options_with_id($fields, $value);
    } else {
        return $fields[$value];
    }
}

function getAssignField($aow_field, $view, $value){
    global $app_list_strings;

    $value = json_decode(html_entity_decode_utf8($value), true);

    $roles = get_bean_select_array(true, 'ACLRole','name', '','name',true);

    if(!file_exists('modules/SecurityGroups/SecurityGroup.php')){
        unset($app_list_strings['aow_assign_options']['security_group']);
    }
    else{
        $securityGroups = get_bean_select_array(true, 'SecurityGroup','name', '','name',true);
    }

    $field = '';

    if($view == 'EditView'){
        $field .= "<select type='text' name='$aow_field".'[0]'."' id='$aow_field".'[0]'."' onchange='assign_field_change(\"$aow_field\")' title='' tabindex='116'>". get_select_options_with_id($app_list_strings['aow_assign_options'], $value[0]) ."</select>&nbsp;&nbsp;";
        if(!file_exists('modules/SecurityGroups/SecurityGroup.php')){
            $field .= "<input type='hidden' name='$aow_field".'[1]'."' id='$aow_field".'[1]'."' value=''  />";
        }
        else {
            $display = 'none';
            if($value[0] == 'security_group') $display = '';
            $field .= "<select type='text' style='display:$display' name='$aow_field".'[1]'."' id='$aow_field".'[1]'."' title='' tabindex='116'>". get_select_options_with_id($securityGroups, $value[1]) ."</select>&nbsp;&nbsp;";
        }
        $display = 'none';
        if($value[0] == 'role' || $value[0] == 'security_group') $display = '';
        $field .= "<select type='text' style='display:$display' name='$aow_field".'[2]'."' id='$aow_field".'[2]'."' title='' tabindex='116'>". get_select_options_with_id($roles, $value[2]) ."</select>&nbsp;&nbsp;";
    }
    else {
        $field = $app_list_strings['aow_assign_options'][$value[1]];
    }
    return $field;

}

function getDropdownList($list_id, $selected_value) {
    global $app_list_strings;
    $option = '';
    foreach($app_list_strings[$list_id] as $key => $value) {
        if(base64_decode($selected_value) == $key) {
            $option .= '<option value="'.$key.'" selected>'.$value.'</option>';
        } else if($selected_value == $key) {
            $option .= '<option value="'.$key.'" selected>'.$value.'</option>';
        }
        else {
            $option .= '<option value="'.$key.'">'.$value.'</option>';
        }
    }
    return $option;
}
function getLeastBusyUser($users, $field, SugarBean $bean) {
    $counts = array();
    foreach($users as $id) {
        $c = $bean->db->getOne("SELECT count(*) AS c FROM ".$bean->table_name." WHERE $field = '$id' AND deleted = 0");
        $counts[$id] = $c;
    }
    asort($counts);
    $countsKeys = array_flip($counts);
    return array_shift($countsKeys);
}

function getRoundRobinUser($users, $id) {

    $file = create_cache_directory('modules/AOW_WorkFlow/Users/') . $id . 'lastUser.cache.php';

    if(isset($_SESSION['lastuser'][$id]) && $_SESSION['lastuser'][$id] != '') {
        $users_by_key = array_flip($users); // now keys are values
        $key = $users_by_key[$_SESSION['lastuser'][$id]] + 1;
        if(!empty($users[$key])) {
            return $users[$key];
        }
    }
    else if (is_file($file)){
        require_once($file);
        if(isset($lastUser['User']) && $lastUser['User'] != '') {
            $users_by_key = array_flip($users); // now keys are values
            $key = $users_by_key[$lastUser['User']] + 1;
            if(!empty($users[$key])) {
                return $users[$key];
            }
        }
    }

   return $users[0];
}

function setLastUser($user_id, $id) {

    $_SESSION['lastuser'][$id] = $user_id;

    $file = create_cache_directory('modules/AOW_WorkFlow/Users/') . $id . 'lastUser.cache.php';

    $arrayString = var_export_helper(array('User' => $user_id));

    $content =<<<eoq
<?php
	\$lastUser = {$arrayString};
?>
eoq;

    if($fh = @sugar_fopen($file, 'w')) {
        fputs($fh, $content);
        fclose($fh);
    }
    return true;
}

function getEmailableModules(){
    global $beanFiles, $beanList, $app_list_strings;
    $emailableModules = array();
    foreach($app_list_strings['aow_moduleList'] as $bean_name => $bean_dis) {
        if(isset($beanList[$bean_name]) && isset($beanFiles[$beanList[$bean_name]])){
            require_once($beanFiles[$beanList[$bean_name]]);
            $obj = new $beanList[$bean_name];
            if($obj instanceof Person || $obj instanceof Company){
                $emailableModules[] = $bean_name;
            }
        }
    }
    asort($emailableModules);
    return $emailableModules;
}

function getRelatedEmailableFields($module){
    global $beanList, $app_list_strings;
    $relEmailFields = array();
    $checked_link = array();
    $emailableModules = getEmailableModules();
    if ($module != '') {
        if(isset($beanList[$module]) && $beanList[$module]){
            $mod = new $beanList[$module]();

            foreach($mod->get_related_fields() as $field){
                if(isset($field['link'])) $checked_link[] = $field['link'];
                if(!isset($field['module']) || !in_array($field['module'],$emailableModules) || (isset($field['dbType']) && $field['dbType'] == "id")){
                    continue;
                }
                $relEmailFields[$field['name']] = $field['module'].": ".trim(translate($field['vname'],$mod->module_name),":");
            }

            foreach($mod->get_linked_fields() as $field){
                if(!in_array($field['name'],$checked_link) && !in_array($field['relationship'],$checked_link)){
                    if(isset($field['module']) && $field['module'] != '') {
                        $rel_module = $field['module'];
                    } else if($mod->load_relationship($field['name'])){
                        $relField = $field['name'];
                        $rel_module = $mod->$relField->getRelatedModuleName();
                    }

                    if(in_array($rel_module,$emailableModules)) {
                        if (isset($field['vname']) && $field['vname'] != '') {
                            $relEmailFields[$field['name']] = $app_list_strings['moduleList'][$rel_module] . ' : ' . translate($field['vname'], $mod->module_dir);
                        } else {
                            $relEmailFields[$field['name']] = $app_list_strings['moduleList'][$rel_module] . ' : ' . $field['name'];
                        }
                    }
                }
            }

            array_multisort($relEmailFields, SORT_ASC, $relEmailFields);
        }
    }
    return $relEmailFields;
}

function fixUpFormatting($module, $field, $value)
{
    global $timedate, $beanFiles, $beanList;

    require_once($beanFiles[$beanList[$module]]);
    $bean = new $beanList[$module];
    
    static $boolean_false_values = array('off', 'false', '0', 'no');

    switch($bean->field_defs[$field]['type']) {
        case 'datetime':
        case 'datetimecombo':
            if(empty($value)) break;
            if ($value == 'NULL') {
                $value = '';
                break;
            }
            if ( ! preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/',$value) ) {
                // This appears to be formatted in user date/time
                $value = $timedate->to_db($value);
            }
            break;
        case 'date':
            if(empty($value)) break;
            if ($value == 'NULL') {
                $value = '';
                break;
            }
            if ( ! preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$value) ) {
                // This date appears to be formatted in the user's format
                $value = $timedate->to_db_date($value, false);
            }
            break;
        case 'time':
            if(empty($value)) break;
            if ($value == 'NULL') {
                $value = '';
                break;
            }
            if ( preg_match('/(am|pm)/i',$value) ) {
                // This time appears to be formatted in the user's format
                $value = $timedate->fromUserTime($value)->format(TimeDate::DB_TIME_FORMAT);
            }
            break;
        case 'double':
        case 'decimal':
        case 'currency':
        case 'float':
            if ( $value === '' || $value == NULL || $value == 'NULL') {
                continue;
            }
            if ( is_string($value) ) {
                $value = (float)unformat_number($value);
            }
            break;
        case 'uint':
        case 'ulong':
        case 'long':
        case 'short':
        case 'tinyint':
        case 'int':
            if ( $value === '' || $value == NULL || $value == 'NULL') {
                continue;
            }
            if ( is_string($value) ) {
                $value = (int)unformat_number($value);
            }
            break;
        case 'bool':
            if (empty($value)) {
                $value = false;
            } else if(true === $value || 1 == $value) {
                $value = true;
            } else if(in_array(strval($value), $boolean_false_values)) {
                $value = false;
            } else {
                $value = true;
            }
            break;
        case 'encrypt':
            $value = $this->encrpyt_before_save($value);
            break;
    }
    return $value;

}
