 <?php
/////////// ADMINNAVI \\\\\\\\\
// Typ:       contentmenu
// Rechte:    $chkMe == 4
///////////////////////////////
if(_adminMenu != 'true') exit;
$what = "usercommander";
$where = $where.': '._config_usercommander;
if($chkMe != 4)
{
  $show = error(_error_wrong_permissions, 1);
} else 
{    
	global $db;
	
	if (!isset($_GET['do']))
	{
		$qry_users = db("SELECT nick,id FROM ".$db['users']);
		$options = "";
		while ($user = _fetch($qry_users))
		{
			$options .= '<option value ="'.$user['id'].'">'.$user['nick']."</option>";
		}
		$show = show ("admin/usercommander_main", array("userlist" => $options));

	}
	else
	{
		switch($_GET['do'])
		{
			case 'action':
				switch($_POST['action'])
				{
					case 'all':
						$show = UC_show_user('all',$_POST['userid']);
						break;
				}
				break;
			case 'delete':
				$show = $_GET['part']."-".$_GET['id'];
				break;
			case 'update_profile':
				db("UPDATE ".$db['users']." SET ".
				" id = ".TuneKit_sqlString($_POST['id']).
				", user = ".TuneKit_sqlString($_POST['user']).
				", nick = ".TuneKit_sqlString($_POST['nick']).
				", level = ".TuneKit_sqlString($_POST['level']).
				", email = ".TuneKit_sqlString($_POST['email']).
				", city = ".TuneKit_sqlString($_POST['city']).
				", steamid = ".TuneKit_sqlString($_POST['steamid']).
				", skypename = ".TuneKit_sqlString($_POST['skype']).
				", originid = ".TuneKit_sqlString($_POST['originid']).
				" WHERE id = ".$_GET['id']);
				$show = TuneKit_msg("Profile Updated");
				break;
		}
	}
	$show = show("admin/usercommander", array(	"show" => $show,
											"version" => TuneKit_getVersion(),
											"head" => _config_usercommander,
											"what" => $what,
											));	
	
}

//-----------------Functions-----------------------------------------------------------------------

	function UC_get($get, $id = 0)
	{	
		global $db;
		
		switch ($get)
		{
			case 'userinfo':
				return db("SELECT * from ".$db['users']." where id = ".$id);
			case 'newscomments':
				return db("SELECT * from ".$db['newscomments']." where reg = ".$id);
			case 'forumposts':
				return db("SELECT * from ".$db['f_posts']." where reg = ".$id);
			case 'acomments':
				return db("SELECT * from ".$db['acomments']." where reg = ".$id);
		}
		return false;
	}
	
	function UC_show_user($get, $id)
	{
		global $db;
		$user = mysqli_fetch_object(db("select * from ".$db['users']." where id = ".$id));
		return show("admin/usercommander_show_main", array("entrys" => UC_show_entrys($get, $id),
															"user" => $user->user,
															"nick" => $user->nick,
															"rlname" => $user->rlname,
															"id" => $user->id,
															"level" => $user->level,
															"pwd" => $user->pass,
															"email" => $user->email,
															"city" => $user->city,
															"steamid" => $user->steamid,
															"skype" => $user->skype,
															"originid" => $user->originid));
	}
	
	function UC_show_entrys($get, $id)
	{
		if ($get == 'all'){
			$table = UC_show_entrys('newscomments',$id);
			$table .= UC_show_entrys('forumposts',$id);
			$table .= UC_show_entrys('acomments',$id);
		}
		else
		{
			$qry = UC_get($get, $id);
			if (mysqli_num_rows($qry))
			{
				while ($get_info = _fetch($qry))
				{
					$table .= show("admin/usercommander_show_tr", array("id" => $get_info['id'],
																		"part" => $get,
																		"msg" => substr(re($get_info['comment'].$get_info['text']),0,1000)." ...",
																		"ip" => $get_info['ip']));
				}
			}
			else
			{
				$table = "<tr><td>No Entrys found</td></tr>";
			}
		}
		return show ("admin/usercommander_show", array("show" => $table, "part" => ucfirst($get)));
	}
	
	function UC_delete($get, $id)
	{
		global $db;
	}
	
	function TuneKit_getVersion($xml_url = "http://hd-gamers.de/addons/usercommander/version.xml", $current = "0.1")
	{
		$status = simplexml_load_file($xml_url);
		if ($status->version > $current) 
		{
			$version = '<font color="#FE2E2E">'.$current.'</font> - <a href="'.$status->download.'">Update Downloaden</a>';
		}
		else 
		{
			$version = '<font color="#3ADF00">'.$current.'</font>';
		}
		return $version.'<font color="#BDBDBD"> | Updatecheck by HD-Gamers.de</font>';
		
	}
	
	function TuneKit_msg($msg, $backto = "../admin/?admin=usercommander")
	{
		return show("admin/usercommander_msg",array("msg" => $msg, "link" => $backto));
	}
	
	function TuneKit_sqlString($param) {
		return (NULL === $param ? "NULL" : '"'.mysql_real_escape_string($param).'"');
	}
 
	function TuneKit_sqlInt($param) {
		return (NULL === $param ? "NULL" : intVal ($param));
	}