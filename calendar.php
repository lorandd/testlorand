<?php
// +----------------------------------------------------------------------+
// | WR Time Tracker
// +----------------------------------------------------------------------+
// | Copyright (c) 2004-2006 WR Consulting (http://wrconsulting.com)
// +----------------------------------------------------------------------+
// | LIBERAL FREEWARE LICENSE: This source code document may be used
// | by anyone for any purpose, and freely redistributed alone or in
// | combination with other software, provided that the license is obeyed.
// |
// | There are only two ways to violate the license:
// |
// | 1. To redistribute this code in source form, with the copyright
// |    notice or license removed or altered. (Distributing in compiled
// |    forms without embedded copyright notices is permitted).
// |
// | 2. To redistribute modified versions of this code in *any* form
// |    that bears insufficient indications that the modifications are
// |    not the work of the original author(s).
// |
// | This license applies to this document only, not any other software
// | that it may be combined with.
// |
// +----------------------------------------------------------------------+
// | Contributors: Igor Melnik <igor@rivne.com>
// +----------------------------------------------------------------------+

	require_once('initialize.php');
	import('form.Form');
	import('UserHelper');
	import('CalendarHelper');
	import('SysConfig');
	import('form.check.Validator');
	
	
	if ($auth->isAuthenticated()) {
		$user = new User($auth->getUserId());
	} else {
		Header("Location: login.php");
		exit();
	}
	$is_manager = ($user->isManager() || $user->isCoManager());
	
	if( $is_manager )
	{
		$in_behalf_id	= $request->getParameter('behalfUser',(isset($_SESSION['behalf_id'])?$_SESSION['behalf_id']:$user->getUserId()));
		$_SESSION['behalf_id'] = $in_behalf_id;
		$user->setBehalfId($in_behalf_id);	
	}
	
	/*if ( !$is_manager )
	{
		Header("Location: login.php");
		exit();
	}*/
	
	
        function dd($arg){
            echo '<pre>';
            print_r($arg);
            echo '</pre>';
            die();
        }
        
        function first_empty_days($n){
            
            $empty_cells = '';
            
            for( $i = 0 ; $i < $n ; $i++ ){
                $empty_cells .= '<TD class="empty">&nbsp;</TD>';
            }
            
            return $empty_cells;
            
        }
        
        function months($actual_year,$user_days, $u_login){
            
        	$month = array();
            
            date_default_timezone_set('Europe/Bucharest');
            
            // get user month 
            $json_user_month_data = json_decode( file_get_contents("http://192.168.0.3/lynx-admin/admin/prepare_data/$actual_year/-1/$u_login") );
			$arr_user_month = array();
			
			foreach($json_user_month_data as $key => $row)
            {
            	$tmp = ((array)$row);
            	$key = key($tmp);
            	$row = $tmp[$key];
            	$arr_user_month[$key] = $row;
            }
			
            $half_days = 0;
            for($m = 1; $m <= 12; $m++){
                
                //start day
            	$d = new DateTime($actual_year.'-'.$m.'-'.'01');                
            	$last_day_of_month = (int)$d->format('t');
                $month_full = $d->format('F');
                $extra_hours = 0;
                
                $month[$month_full] = '';
                $month[$month_full] .= '<table border="0" cellpadding="1" cellspacing="1" width="100%" class="month_table">
                                        	<thead>
                                        		<tr><td colspan="7">'.$month_full.'</td></tr>
                                        	</thead>
											<tr>
                                                <th class="we">su</td>
                                                <th>mo</td>
                                                <th>tu</td>
                                                <th>we</td>
                                                <th>th</td>
                                                <th>fr</td>
                                                <th class="we">sa</td>
                                            </tr>';
                
            	$week = 0;
                
                for($day = 1 ; $day <= $last_day_of_month ; $day++){
                    
                    $current_day = $actual_year.'-'.(($m < 10) ? "0" : "").$m.'-'.(($day < 10) ? "0" : "").$day;
                    
                	$w = new DateTime($current_day);
                    $w = $w->format('w'); //Numeric representation of the day of the week
                    
                    if( !($week%7) )
                        $month[$month_full] .= '<TR>';

                    $current_day_class = CalendarHelper::date_analizer($user_days, $current_day);
					$link_desc = CalendarHelper::get_day_description($user_days, $current_day);
                    
					//check half dayss
					if( (strpos( $current_day_class, 'vdm') !== FALSE) || (strpos( $current_day_class, 'vda') !== FALSE) )
						++$half_days;
						
					if($half_days != 0 && $half_days %2 == 0)
					{
						$current_day_class .= " end_half_day";
						$half_days = 0;
					}
					
                    if($day == 1)
                    {
                        $week += $d->format('w');
                        $month[$month_full] .= first_empty_days((int)$d->format('w'));
                        
                    }
                    	
                    $month[$month_full] .= '<td class="'.($current_day_class).'"><a href="mytime.php?date='.date("m/d/Y", strtotime($current_day)).'" title="'.$link_desc.'">'.$day.'</a></TD>';                        
                    $week++;
                    
                    
                    if( !($week%7) )
                        $month[$month_full] .= '</TR>';                    
                  
                }
                $month[$month_full] .= '</table>';
                
                global $is_manager; 
                
                $u_key = $actual_year."-".$m."-".$u_login;
                $user_month_data = isset($arr_user_month[$u_key]) ? $arr_user_month[$u_key] : array();
				
				if ($user_month_data)
				{
	                if ($is_manager)
	                {
                	    if ($user_month_data->reported_hours > 0)
		                {
							$month_summary = "<table class='summary'>
												<tr>
													<td>User Month Hours:</td>
													<td><b>{$user_month_data->user_min_hours}</b></td>
												</tr>
												<tr>
													<td>Reported Hours:</td>
													<td><b>{$user_month_data->reported_hours}</b></td>
												</tr>
												<tr>
													<td>Hours Missing:</td>
													<td><b>{$user_month_data->hours_missing}</b></td>
												</tr>
												<tr>
													<td>Extra Hours:</td>
													<td><b>{$user_month_data->extra_hours}</b></td>
												</tr>
											</table>";  
												
							$month[$month_full].= $month_summary;              
						}
					}
					else 
					{
						if( $user_month_data->extra_hours > 0 )
						{
							$month_summary = "<table class='summary'>
												<tr>
													<td>Extra Hours:</td>
													<td><b>{$user_month_data->extra_hours}</b></td>
												</tr>
											</table>";  
												
							$month[$month_full].= $month_summary;
						}
					}
				}
				
            }
            return $month;
        }
        
        

	$ud = UserHelper::findUserById($user->getUserId(),$user);
	$user_id = (isset($_SESSION['behalf_id'])?$_SESSION['behalf_id']:$user->getUserId());
   
    $bd = UserHelper::findUserById($user_id,$user);
	
    if( !$bd )
    	die("Invalid user"); 
    $u_login = $bd['u_login'];
    
    $u_login = "nnagy";
    
    $form = new Form('profileForm');
	$user_list = UserHelper::findAllUsers($user);

	$form->addInput(
			array(
			"type"=>"combobox",
			"onchange"=>"if(this.form) this.form.submit();",
			"name"=>"behalfUser",
			"value"=>$user_id,
			"data"=>$user_list,
			"datakeys"=>array("u_id","u_name"),
	));
    
	$smarty->assign("ldap_auth", $ud['u_ldap_auth']);
	
	$smarty->assign_by_ref("errors", $errors);
	$smarty->assign("forms",array($form->getName()=>$form->toArray()));
    $smarty->assign("onload","onLoad = \"document.profileForm.uname.focus()\"");
    $smarty->assign("userdet_string",UserHelper::getUserDetailsString($user,$GLOBALS["I18N"]));
    $smarty->assign("title_page",$i18n->getKey("form.profile.prof_str"));
    
    //get value from link
    $actual_year = $_POST['years'];
    $current_year = date("Y");
   
    if (!is_numeric($actual_year) ) $actual_year = $current_year;
    
    if ($actual_year < $current_year) $actual_year = $current_year;
    
    $used_holiday_days = 0;
    $holiday_days = 0;
    $holiday_days_prev_year = 0;
    $hours_changed = 0;
    $total_medical_days = 0;
    $user_days = CalendarHelper::user_dates($u_login,$actual_year, $used_holiday_days, $holiday_days, $holiday_days_prev_year, $hours_changed, $total_medical_days);        //$ud['u_login']
    
    $months = months($actual_year,$user_days, $u_login);
    
    $years = array();
    for( $i = $current_year; $i<= ($actual_year + 10); $i++ )
   		$years[$i] = $i;
   	
   	$smarty->assign('years', $years);
    $smarty->assign('mySelect', $actual_year);
    $smarty->assign('months', $months);
  	$smarty->assign('is_manager', $is_manager);
  	$smarty->assign('used_holiday_days', $used_holiday_days);
  	$smarty->assign('holiday_days', $holiday_days);
  	$smarty->assign('holiday_days_prev_year', $holiday_days_prev_year);
  	//$smarty->assign('hours_changed', $hours_changed);
  	$smarty->assign('total_medical_days', $total_medical_days);
  	$smarty->assign('days_left', ($holiday_days - $used_holiday_days + $holiday_days_prev_year));
  	$smarty->assign('show_days_summary', ($actual_year == date("Y")) );
  	$smarty->assign("userdet_string",UserHelper::getUserDetailsString($user,$GLOBALS["I18N"]));
    
  	/*
  	 	{if $is_manager}	
		<td colspan="3" class="details">
			<p>Hours changed: <b>{$hours_changed}</b></p>
		</td>
		{/if}
  	 */
    if ($ud) {
    	$smarty->assign("content_page_name","profile_date.tpl");
    } else {
    	$smarty->assign("content_page_name","syserror.tpl");
    }
  	$smarty->display(INDEX_TEMPLATE);

?>