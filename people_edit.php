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
	import('ProjectHelper');
	
	if ($auth->isAuthenticated()) {
		$user = new User($auth->getUserId());
		if ($user->isAdministrator()) {
			Header("Location: admin.php");
			exit();
		}
       if($user->isClient())
        {
            Header("Location: reports.php");
            exit();
        }
	} else {
		Header("Location: login.php");
		exit();
	}

	$cl_userid = (int)$request->getParameter('ppl_id');
	if ($cl_userid>0) {
		$_SESSION["cl_userid"] = $cl_userid;
	}
	if ($cl_userid==0) {
		$cl_userid = $_SESSION["cl_userid"];
	}
	
	//$projects = ProjectHelper::findAllProjects($user);
	$projects = UserHelper::findProjectsBinded($cl_userid, $user->getOwnerId(), false);
	$projects = mu_sort($projects, "p_name");
	
	$ud = UserHelper::findUserById($cl_userid, $user, false);
	
    if ($request->getMethod()=="POST") {
		$cl_name  = $request->getParameter('name');
		$cl_name  = trim($cl_name);
		$cl_email  = $request->getParameter('email');
		$cl_email	= trim(strtolower($cl_email));
		$cl_password1	= $request->getParameter('pas1');
  		$cl_password2	= $request->getParameter('pas2');
  		$cl_rate	= $request->getParameter('rate');
  		$cl_comanager	= $request->getParameter('comanager');
  		$cl_projects = $request->getAttribute("projects");
        $cl_is_client	= $request->getParameter('is_client');
  		$ud["projects"] = array();
  		foreach ($projects as $p) {
  			$it = $p;
  			$it["ub_checked"] = 0;
  			if (!isset($it["ub_rate"])) $it["ub_rate"] = 0;
  			if (is_array($cl_projects)) 
  				foreach ($cl_projects as $ip) 
  					if ($p["p_id"]==$ip) $it["ub_checked"] = 1;
  			if ($request->getAttribute("rate_".$p["p_id"])) $it["ub_rate"] = $request->getAttribute("rate_".$p["p_id"]);
			$it["ub_alias"] = $request->getAttribute("alias_".$p["p_id"]);

            $ud["projects"][] = $it;
  		}
  		
  		if( $user->isAdministrator() || $user->isManager() || $user->isCoManager() )
  		{
	  		//get holidays post values
	  		$curent_year = date("Y"); 
	  		
	  		for( $i= $curent_year; $i<= ($curent_year+5); $i++)
	  		{
	  			$holiday_days = $request->getAttribute("holiday_days_".$i."_".$cl_userid);
	  			$holiday_days_prev_year = $request->getAttribute("holiday_days_prev_year".$i."_".$cl_userid);
	  			
	  			if( is_numeric($holiday_days) && is_numeric($holiday_days_prev_year) )
	  			{
	  				//check if exits
	  				$holiday_days_row = UserHelper::getUserHolidayDays($cl_userid, $i);
	  				
	  				if( $holiday_days_row )
	  				{
	  					//update
	  					UserHelper::updateUserHolidayDays($holiday_days_row['id'], $holiday_days, $holiday_days_prev_year);
	  				}
	  				else
	  				{
	  					//otherwise insert a new row
	  					$data = array(
	  									'u_id' => $cl_userid, 
	  									'year' => $i, 
	  									'holiday_days' => $holiday_days,
	  									'holiday_days_prev_year' => $holiday_days_prev_year );
	  					UserHelper::insertUserHolidayDays($data);
	  				}
	  				
	  			}
	  			
	  		}
  		}
  		
	} else {
        $cl_name	= $ud["u_name"];
        $cl_email	= $ud["u_login"];
        $cl_rate = str_replace(".",$i18n->getFloatDelimiter(),$ud["u_rate"]);
        $cl_comanager = $ud["u_comanager"];
        $cl_is_client = $ud["u_is_client"];
       	$cl_projects = array();
       	foreach($ud["projects"] as $p) {
			if ($p["ub_checked"])
				$cl_projects[] = $p["p_id"];
		}
	}
	
	//get holidays
	$holidays = UserHelper::getUserHolidayDays($cl_userid);
	$count_holidays = count($holidays);
	$actual_year = $year = date("Y");
	
	if( $count_holidays < 5 )
	{
		$year = $actual_year + $count_holidays;
		while( $year != $actual_year + 5 )
		{
			
			$holidays[] = array( 
										'id' 						=> '',
										'u_id' 						=> $cl_userid,
										'year'						=> $year, 
										'holiday_days' 				=> '',
										'holiday_days_prev_year' 	=> '',
										'days_left'					=> ''
 			);
 			$year++;
		}	
	}
        
	$form = new Form('peopleForm');
	$form->addInput(array("type"=>"text","maxlength"=>"100","name"=>"name","style"=>"width:300;","value"=>$cl_name));
    $form->addInput(array("type"=>"text","maxlength"=>"100","name"=>"email","style"=>"width:300;","value"=>$cl_email,"enable"=>($user->getUserId()!=$ud["u_id"])));
    $form->addInput(array("type"=>"text","maxlength"=>"30","name"=>"pas1","aspassword"=>true,"value"=>@$cl_password1));
    $form->addInput(array("type"=>"text","maxlength"=>"30","name"=>"pas2","aspassword"=>true,"value"=>@$cl_password2));
    $form->addInput(array("type"=>"floatfield","maxlength"=>"10","name"=>"rate","format"=>".2","value"=>$cl_rate));
    $form->addInput(array("type"=>"checkbox","name"=>"comanager","data"=>"1","value"=>@$cl_comanager));
    $form->addInput(array("type"=>"checkbox","name"=>"is_client","data"=>"1","value"=>@$cl_is_client));

    /*$col_count = ( ceil(count($projects)/2)>7 ? ceil(count($projects)/2) : 7);
    $form->addInput(array("type"=>"checkboxgroup","name"=>"projects",
    	"data"=>$projects,"datakeys"=>array("p_id","p_name"),"groupin"=>$col_count,
    	"value"=>@$cl_projects));*/
    	
    import("form.Table");
    import("form.TableColumn");
    class NameCellRenderer extends DefaultCellRenderer {
		function toRender(&$table, $value, $row, $column ) {
			$this->setOptions(array("width"=>200,"valign"=>"top"));
			$this->setValue($value);
			return $this->toString();
		}
	}
    class RateCellRenderer extends DefaultCellRenderer {
		function toRender(&$table, $value, $row, $column ) {
			global $ud;
			$field = new FloatField("rate_".$table->getValueAtName($row,"p_id"), $table->getValueAtName($row, "p_rate"));
			$field->setFormName($table->getFormName());
			$field->setLocalization($GLOBALS["I18N"]);
			$field->setSize(5);
			$field->setFormat(".2");
			if ($ud["projects"])
			foreach ($ud["projects"] as $p) {
				if ($p["p_id"]==$table->getValueAtName($row,"p_id")) $field->setValue(@$p["ub_rate"]);
			}
	    	$this->setValue($field->toStringControl());
			return $this->toString();
		}
	}
    class AliasCellRenderer extends DefaultCellRenderer {
		function toRender(&$table, $value, $row, $column ) {
			global $ud;
			$field = new FloatField("alias_".$table->getValueAtName($row,"p_id"), $table->getValueAtName($row, "p_alias"));
			$field->setFormName($table->getFormName());
			$field->setLocalization($GLOBALS["I18N"]);
			$field->setSize(15);
			if ($ud["projects"])
			foreach ($ud["projects"] as $p) {
				if ($p["p_id"]==$table->getValueAtName($row,"p_id")) $field->setValue(@$p["ub_alias"]);
			}
	    	$this->setValue($field->toStringControl());
			return $this->toString();
		}
	}
	
	class HolidayDaysCellRenderer extends DefaultCellRenderer {
		function toRender(&$table, $value, $row, $column ) {
			global $holidays;
			
			$id = "holiday_days_".$table->getValueAtName($row,"year")."_".$table->getValueAtName($row,"u_id");
			$field = new FloatField($id, $table->getValueAtName($row, "holiday_days"));
			$field->setFormName($table->getFormName());
			$field->setLocalization($GLOBALS["I18N"]);
			$field->setSize(15);
			if ($holidays)
			{
				foreach ($holidays as $p) {
					if ($p["id"]==$table->getValueAtName($row,"id")) $field->setValue(@$p["holiday_days"]);
				}
			}
			$this->setValue($field->toStringControl());
			return $this->toString();
		}
	}
	
	class HolidayDaysPrevYearCellRenderer extends DefaultCellRenderer {
		function toRender(&$table, $value, $row, $column ) {
			global $holidays;
			
			$id = "holiday_days_prev_year".$table->getValueAtName($row,"year")."_".$table->getValueAtName($row,"u_id");
			$field = new FloatField($id, $table->getValueAtName($row, "holiday_days_prev_year"));
			$field->setFormName($table->getFormName());
			$field->setLocalization($GLOBALS["I18N"]);
			$field->setSize(15);
			if ($holidays)
			{
				foreach ($holidays as $p) {
					if ($p["id"]==$table->getValueAtName($row,"id")) $field->setValue(@$p["holiday_days_prev_year"]);
				}
			}
			$this->setValue($field->toStringControl());
			return $this->toString();
		}
	}
	
    $table = new Table("projects");
    $table->setIAScript("selectProject");
    $table->setTableOptions(array("width"=>"100%","cellspacing"=>"1","cellpadding"=>"3","border"=>"0"));
    $table->setRowOptions(array("valign"=>"top","class"=>"tableHeader"));
   	$table->setMultiSelect(true);
    $table->setData($projects);
    $table->setKeyField("p_id");
    $table->setValue($cl_projects);
    $table->addColumn(new TableColumn("p_name",$i18n->getKey('form.people.th.project'), new NameCellRenderer()));
    if(!$cl_is_client)
    {
        $table->addColumn(new TableColumn("p_rate",$i18n->getKey('form.people.th.rate'), new RateCellRenderer()));
        $table->addColumn(new TableColumn("p_alias",$i18n->getKey('form.people.th.alias'), new AliasCellRenderer()));
    }
    $form->addInputElement($table);
    
    //set holidays
    $table_holidays = new Table("holidays");
    $table_holidays->setIAScript(null);
    $table_holidays->setTableOptions(array("width"=>"100%","cellspacing"=>"1","cellpadding"=>"3","border"=>"0","id"=>"holidays_table"));
    $table_holidays->setRowOptions(array("valign"=>"top","class"=>"tableHeader"));
   	$table_holidays->setMultiSelect(false);
   	$table_holidays->setInteractive(false);
   	$table_holidays->setData($holidays);
    $table_holidays->setKeyField("id");
    //$table->setValue($cl_holidays);
    $table_holidays->addColumn(new TableColumn("year",$i18n->getKey('form.people.th.year'), new NameCellRenderer()));
    $table_holidays->addColumn(new TableColumn("holiday_days",$i18n->getKey('form.people.th.holiday_days'), new HolidayDaysCellRenderer()));
    $table_holidays->addColumn(new TableColumn("holiday_days_prev_year",$i18n->getKey('form.people.th.holiday_days_prev_year'), new HolidayDaysPrevYearCellRenderer()));
    $form->addInputElement($table_holidays);
    
    $form->addInput(array("type"=>"hidden","name"=>"ppl_id","value"=>$cl_userid));
	$form->addInput(array("type"=>"submit","name"=>"btsubmit","value"=>$i18n->getKey('button.save')));
	
	if ($request->getMethod()=="POST") {
		import('form.check.Validator');
		
		$validator = new Validator($cl_name);
		$validator->validateSpaceString();
		$validator->validateEmptyString();
		if (!$validator->isValid()) {
			$errors->add("name",$i18n->getKey("errors.wrong"),$i18n->getKey("form.people.name"));	
		}
		
		$validator = new Validator($cl_email);
		$validator->validateSpaceString();
		$validator->validateEmptyString();
		$validator->validateEmail();
		if (!$validator->isValid()) {
	    	$errors->add("email",$i18n->getKey("errors.wrong"),$i18n->getKey("form.people.email"));
	    }
	    
	    if ($cl_password1) {
		    $validator = new Validator($cl_password1);
			//$validator->validateSpaceString();
			$validator->validateEmptyString();
			//$validator->validateLatinCharset();
	    	if (!$validator->isValid()) {
	    		$errors->add("password",$i18n->getKey("errors.wrong"),$i18n->getKey("form.people.pas1"));
	    	} elseif (!($cl_password1 === $cl_password2))
	    		$errors->add("passwords",$i18n->getKey("errors.compare"),$i18n->getKey("form.people.pas1"),$i18n->getKey("form.people.pas2"));
	    }
    		
    	$validator = new Validator($cl_rate);
		$validator->validateFloat($i18n->getFloatDelimiter());
		if ($cl_rate && !$validator->isValid()) {
	    	$errors->add("rate",$i18n->getKey("errors.wrong"),$i18n->getKey("form.people.rate"));
	    }
    
		if ($errors->isEmpty()) {
			$ulo = UserHelper::findUserByLogin($cl_email);
			if (($ulo && $cl_userid==$ulo["u_id"]) || (!$ulo) ) {
                if (UserHelper::updateEmployee($user, $cl_userid, $cl_name, $cl_email, $cl_password1, $cl_rate, $cl_comanager, $ud["projects"],$cl_is_client)) {
					// reload personal data for current user
					if ($cl_userid==$user->getUserId()) $user->setUserId($user->getUserId());
					// reload data for behalf user
					if ($user->getBehalfId()>0 && $cl_userid==$user->getBehalfId()) $user->setBehalfId($cl_userid);
					
					//Header("Location: people.php");
					Header("Location: people_edit.php?ppl_id=$cl_userid");
					exit();
				} else
					$errors->add("edituser",$i18n->getKey("errors.user_update"));
			} else 
				$errors->add("edituser",$i18n->getKey("errors.user_exist"));
		}			
	} // post
	
	$smarty->assign_by_ref("errors", $errors);
    $smarty->assign("forms",array($form->getName()=>$form->toArray()));
    $smarty->assign("onload","onLoad = \"document.peopleForm.name.focus()\"");
    $smarty->assign("userdet_string",UserHelper::getUserDetailsString($user,$GLOBALS["I18N"]));
    $smarty->assign("title_page",$i18n->getKey("form.people.edit_str"));
    $smarty->assign("ud",$ud);
  	if ($ud) {
    	$smarty->assign("content_page_name","people_edit.tpl");
    } else {
    	$smarty->assign("content_page_name","syserror.tpl");
    }
  	$smarty->display(INDEX_TEMPLATE);
?>