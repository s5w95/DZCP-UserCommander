 <?php
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
				if ($_POST['action'] == 'show'){
					if ($_POST['part'] == 'user'){
					$show = UC_show_user($_POST['part'],$_POST['userid'], false);
					}
					else {
					$show = UC_show_user($_POST['part'],$_POST['userid']);
					}
				}	
				else if ($_POST['action'] == 'remove')
				{
					$show = UC_delete($_POST['part'],$_POST['userid'],1);
					$show .= TuneKit_msg("Done");
				}				
				break;
			case 'delete':
				$show = UC_delete($_GET['part'],$_GET['id'],$_GET['all']);
				$show .= TuneKit_msg("Done");
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
			case 'forumthreads':
				return db("SELECT * from ".$db['f_threads']." where t_reg = ".$id);
			case 'messages':
				return db("SELECT * from ".$db['msg']." where von = ".$id);
			case 'cw_comments':
				return db("SELECT * from ".$db['cw_comments']." where reg = ".$id);
		}
		return false;
	}
	
	function UC_show_user($get, $id, $showentrys = true)
	{
		global $db;
		$user = mysqli_fetch_object(db("select * from ".$db['users']." where id = ".$id));
		$entrys = ""; $s_entrys = "";
		if ($showentrys) { $entrys = UC_show_entrys($get, $id); $s_entrys = "Entrys:"; }
		return show("admin/usercommander_show_main", array("entrys" => $entrys,
															"s_entrys" => $s_entrys,
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
			$table .= UC_show_entrys('forumthreads',$id);
			$table .= UC_show_entrys('messages',$id);
			$table .= UC_show_entrys('cw_comments',$id);
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
																		"msg" => substr(re($get_info['comment'].$get_info['text'].$get_info['t_text'].$get_info['nachricht']),0,1000)." ...",
																		"ip" => $get_info['ip']));
				}
			}
			else
			{
				$table = "<tr><td>No Entrys found</td></tr>";
			}
		}
		return show ("admin/usercommander_show", array("show" => $table, "part" => ucfirst($get), "id" => $id));
	}
	
	function UC_delete($get, $id, $all = 0)
	{
		global $db;
		if ($all)
		{
			if($get == 'all')
			{
				$show = UC_delete('newscomments',$id,1);
				$show .= UC_delete('forumposts',$id,1);
				$show .= UC_delete('acomments',$id,1);
				$show .= UC_delete('forumthreads',$id,1);
				$show .= UC_delete('messages',$id,1);
				$show .= UC_delete('cw_comments',$id,1);
				$show .= UC_delete('user',$id,0);
			}
			else
			{
				$qry = UC_get($get, $id);
				while ($get_entrys = _fetch($qry))
				{
					$show .= UC_delete($get, $get_entrys['id']);
				}
			}
		}
		else
		{
			switch($get)
			{
				case 'newscomments':
					db('DELETE FROM '.$db['newscomments'].' WHERE id = '.$id);
					break;
				case 'forumposts':
					db('DELETE FROM '.$db['f_posts'].' WHERE id = '.$id);
					break;
				case 'acomments':
					db('DELETE FROM '.$db['acomments'].' WHERE id = '.$id);
					break;
				case 'forumthreads':
					db('DELETE FROM '.$db['f_threads'].' WHERE id = '.$id);
					break;
				case 'messages':
					db('DELETE FROM '.$db['msg'].' WHERE id = '.$id);
					break;
				case 'cw_comments':
					db('DELETE FROM '.$db['cw_comments'].' WHERE id = '.$id);
					break;
				case 'user':
					db('DELETE FROM '.$db['users'].' WHERE id = '.$id);
					break;
			}
			return $show."<tr><td>".$get." with ID: ".$id." deleted</td></tr>";
		}		
		return $show."<tr><td>".$get." from user-ID: ".$id." deleted</td></tr>";
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
