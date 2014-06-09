 <?php
/////////// ADMINNAVI \\\\\\\\\
// Typ:       contentmenu
// Rechte:    $chkMe == 4
///////////////////////////////

if(_adminMenu != 'true') exit;
$what = "usercommander";
$uc_version = 1.0;
$where = $where.': '._config_usercommander;
if($chkMe != 4)
{
    $show = error(_error_wrong_permissions, 1);
} else
{
    set_last_site();
    global $db;


    if(isset($_GET['userid'])) $_SESSION['uc_user'] = $_GET['userid'];
    $qry_users = db("SELECT nick,id FROM ".$db['users']);
    $options = "";
    while ($user = _fetch($qry_users))
    {
        $uc_selected = $_SESSION['uc_user'] == $user['id'] ? 'selected' : "";
        $options .= '<option value ="'.$user['id'].'" '.$uc_selected.'>'.$user['nick']."</option>";
    }

    if (isset($_GET['do']))
    {
        $uc_user = new CommandUser($_SESSION['uc_user']);
        if (!empty($_SESSION['uc_notice'])) {
            $notice = show('admin/usercommander_notice', array('content' => $_SESSION['uc_notice']));
            $_SESSION['uc_notice'] = "";
        } else {
            $notice = "";
        }

        switch($_GET['do'])
        {
            case 'action':
                if ($_GET['action'] == 'show'){
                    if ($_GET['part'] == 'user'){
                        $show = $uc_user->render_user($_GET['part'], false);
                    }
                    else {
                        $show = $uc_user->render_user($_GET['part']);
                    }
                }
                else if ($_GET['action'] == 'remove')
                {
                    $uc_user->delete_from_user($_GET['part'],1);
                    $_SESSION['uc_notice'] = $uc_user->get_log_table;
                    header('Location: '.$_SESSION['uc_last']);
                }
                break;
            case 'delete':
                $uc_user->delete_from_user($_GET['part'],$_GET['id']);
                $_SESSION['uc_notice'] = $uc_user->get_log_table();
                header('Location: '.$_SESSION['uc_last']);
                break;
            case 'update_profile':
                $uc_user->update_profile_from_get();
                $_SESSION['uc_notice'] = '<p>Profile Updated</p>';
                header('Location: '.$_SESSION['uc_last']);
                break;
        }
    }
    $show = show("admin/usercommander", array(	"show" => $show,
        "version" => TuneKit_getVersion($uc_version),
        "head" => _config_usercommander,
        "what" => $what,
        "notice" => $notice,
        "userlist" => $options
    ));
}

function set_last_site() {
    $_SESSION['uc_last'] = $_SESSION['uc_cur_url'];
    $_SESSION['uc_cur_url'] = ((empty($_SERVER['HTTPS'])) ? 'http' : 'https') .'://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

class CommandUser {

    private $db;
    private $log = "";

    private $user, $nick, $rlname, $pass, $level, $email, $city, $steamid, $skypename, $originid;

    public function __construct($userid) {
        global $db;
        $this->db = $db;
        if (empty($userid)) $userid = 0;
        $this->id = $userid;
        $this->load_user_from_db();
    }

    private function load_user_from_db(){
        $user = mysqli_fetch_object(db("select * from ".$this->db['users']." where id = ".$this->id));
        foreach ($user as $field => $value) {
            $this->$field = $value;
        }
    }

    private function get_userinformation_qry_from($get)
    {
        switch ($get)
        {
            case 'userinfo':
                return db("SELECT * from ".$this->db['users']." where id = ".$this->id);
            case 'newscomments':
                return db("SELECT * from ".$this->db['newscomments']." where reg = ".$this->id);
            case 'forumposts':
                return db("SELECT * from ".$this->db['f_posts']." where reg = ".$this->id);
            case 'acomments':
                return db("SELECT * from ".$this->db['acomments']." where reg = ".$this->id);
            case 'forumthreads':
                return db("SELECT * from ".$this->db['f_threads']." where t_reg = ".$this->id);
            case 'messages':
                return db("SELECT * from ".$this->db['msg']." where von = ".$this->id);
            case 'cw_comments':
                return db("SELECT * from ".$this->db['cw_comments']." where reg = ".$this->id);
        }
        return false;
    }

    public function update_profile_from_get() {
        $password = !empty($_POST['pass']) ? "pwd ='".md5($_POST['pass'])."'" : '';
        db("UPDATE ".$this->db['users']." SET ".
            "user = ".TuneKit_sqlString($_POST['user']).
            ", nick = ".TuneKit_sqlString($_POST['nick']).
            ", level = ".TuneKit_sqlString($_POST['level']).
            ", email = ".TuneKit_sqlString($_POST['email']).
            ", city = ".TuneKit_sqlString($_POST['city']).
            ", steamid = ".TuneKit_sqlString($_POST['steamid']).
            ", rlname = ".TuneKit_sqlString($_POST['rlname']).
            ", skypename = ".TuneKit_sqlString($_POST['skypename']).
            ", originid = ".TuneKit_sqlString($_POST['originid']).
            ', '.$password." WHERE id = ".$_GET['id']);
        $this->load_user_from_db();
    }

    function delete_from_user($get, $id)
    {
        if ($id < 0)
        {
            if($get == 'all')
            {
                $this->delete_from_user('newscomments',-1);
                $this->delete_from_user('forumposts',-1);
                $this->delete_from_user('acomments',-1);
                $this->delete_from_user('forumthreads',-1);
                $this->delete_from_user('messages',-1);
                $this->delete_from_user('cw_comments',-1);
                $this->delete_from_user('user',$this->id);
            }
            else
            {
                $qry = $this->get_userinformation_qry_from($get);
                while ($get_entrys = mysqli_fetch_assoc($qry))
                {
                    $this->delete_from_user($get, $get_entrys['id']);
                }
            }
            $this->log("$get from user ".$this->nick." deleted");
        }
        else
        {
            switch($get)
            {
                case 'newscomments':
                    db('DELETE FROM '.$this->db['newscomments'].' WHERE id = '.$id);
                    break;
                case 'forumposts':
                    db('DELETE FROM '.$this->db['f_posts'].' WHERE id = '.$id);
                    break;
                case 'acomments':
                    db('DELETE FROM '.$this->db['acomments'].' WHERE id = '.$id);
                    break;
                case 'forumthreads':
                    db('DELETE FROM '.$this->db['f_threads'].' WHERE id = '.$id);
                    break;
                case 'messages':
                     db('DELETE FROM '.$this->db['msg'].' WHERE id = '.$id);
                    break;
                case 'cw_comments':
                    db('DELETE FROM '.$this->db['cw_comments'].' WHERE id = '.$id);
                    break;
                case 'user':
                    db('DELETE FROM '.$this->db['users'].' WHERE id = '.$id);
                    break;
            }
            $this->log("$get with ID: $id from user ".$this->nick." deleted");
        }
    }

    private function log($str) {
        $this->log .= "<tr><td>$str</td></tr>";
    }

    public function get_log_table() {
        return '<table style="color:white">'.$this->log.'</table>';
    }

    public function render_user($get, $showentrys = true) {
        $entrys = "";
        $s_entrys = "";

        if ($showentrys) {
            $entrys = $this->render_entrys($get);
            $s_entrys = "Entrys:";
        }
        return show("admin/usercommander_show_main", array("entrys" => $entrys,
            "s_entrys" => $s_entrys,
            "user" => $this->user,
            "nick" => $this->nick,
            "rlname" => $this->rlname,
            "id" => $this->id,
            "level" => $this->level,
            "pwd" => $this->pass,
            "email" => $this->email,
            "city" => $this->city,
            "steamid" => $this->steamid,
            "skypename" => $this->skypename,
            "originid" => $this->originid));
    }

    function render_entrys($get)
    {
        $table = "";
        if ($get == 'all'){
            $table =  $this->render_entrys('newscomments');
            $table .= $this->render_entrys('forumposts');
            $table .= $this->render_entrys('acomments');
            $table .= $this->render_entrys('forumthreads');
            $table .= $this->render_entrys('messages');
            $table .= $this->render_entrys('cw_comments');
        }
        else
        {
            $qry = $this->get_userinformation_qry_from($get);
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
        return show("admin/usercommander_show", array("show" => $table, "part" => ucfirst($get), "username" => $this->user, "id" => -1));
    }
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

function TuneKit_sqlString($param) {
    return (NULL === $param ? "NULL" : "'".mysql_real_escape_string($param)."'");
}

function TuneKit_sqlInt($param) {
    return (NULL === $param ? "NULL" : intVal ($param));
}