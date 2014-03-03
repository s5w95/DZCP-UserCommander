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
		$show = show("admin/usercommander", array(	"version" => TuneKit_getVersion(),
													"head" => _config_usercommander,
													"what" => $what,
													"userlist" => $options));
		
	}
	else
	{
		switch($_GET['do'])
		{
			case 'action':
				switch($_POST['action'])
				{
					case 1:
						$show = UC_show('news',$_POST['userid']);
						break;
				}
				break;
		}
	}
}

//-----------------Functions-----------------------------------------------------------------------

	function UC_get($get, $id = 0)
	{	
		global $db;
		
		switch ($get)
		{
			case 'news':
				return db("SELECT * from ".$db['newscomments']." where reg = ".$id);
			case 'forumposts':
				return db("SELECT * from ".$db['forumposts']." where reg = ".$id);
		}
		return false;
	}
	
	function UC_show($get, $id)
	{
		$qry = UC_get($get, $id);
		while ($get = _fetch($qry))
		{
			$table .= show("admin/usercommander_show_tr", array("msg" => substr(re($get['comment']),0,1000)." ...",
																"ip" => $get['ip']));
		}
		return show("admin/usercommander_show", array("show" => $table));
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