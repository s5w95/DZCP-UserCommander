<?php
/**
 * @package: DZCP-UserCommander
 * @author: Tune389 - Dayline-Studio
 * @link: http://hd-gamers.de
 */

if(_adminMenu != 'true') exit;
$what = "usercommander";
$where = $where.': '._config_usercommander;
$uc_version = 1.1;
if($chkMe != 4)
{
    $show = error(_error_wrong_permissions, 1);
} else {

    $_SESSION['uc_security'] = empty($_SESSION['uc_security']) ? mkpwd() : $_SESSION['uc_security'];
    set_last_site();
    if(isset($_GET['userid'])) $_SESSION['uc_user'] = $_GET['userid'];

    $qry_users = db("SELECT nick,id FROM ".$db['users']);
    $options = "";
    $user_active = false;
    while ($user = _fetch($qry_users))
    {
        $uc_selected = '';
        if ($_SESSION['uc_user'] == $user['id']) {
            $uc_selected = 'selected';
            $user_active = true;
        }
        $options .= '<option value ="'.$user['id'].'" '.$uc_selected.'>'.$user['nick']."</option>";
    }
    $uc_user = new CommandUser($_SESSION['uc_user']);
    if (!empty($_SESSION['uc_notice'])) {
        $color = 'green';
        if (!empty($_SESSION['uc_notice_color'])) $color = $_SESSION['uc_notice_color'];
        $notice = show('admin/usercommander_notice', array('content' => $_SESSION['uc_notice'], 'color' => $color));
        $_SESSION['uc_notice'] = "";
        $_SESSION['uc_notice_color'] = "";
    } else {
        $notice = "";
    }
    if (isset($_GET['do']) & $user_active)
    {
        secure_this_area();
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
                    $uc_user->delete_from_user($_GET['part'],-1);
                    check_work($uc_user);
                    go_back();
                }
                break;
            case 'delete':
                $uc_user->delete_from_user($_GET['part'],$_GET['id']);
                check_work($uc_user);
                go_back();
                break;
            case 'update_profile':
                $uc_user->update_profile_from_get();
                set_notice('<p>Profile from '.$uc_user->nick.' Updated</p>');
                go_back();
                break;
        }
    }
    $show = show("admin/usercommander", array(
        "show" => $show,
        "version" => TuneKit_getVersion(NULL,$uc_version),
        "head" => _config_usercommander,
        "what" => $what,
        "uc_sid" => $_SESSION['uc_security'],
        "notice" => $notice,
        "userlist" => $options
    ));
}

function check_work($uc_user) {
    if ($uc_user->work) {
        set_notice($uc_user->get_log_table());
    } else {
        set_notice('Nothing to do here', 'red');
    }
}
function set_notice($notice, $color = 'green') {
    $_SESSION['uc_notice'] = $notice;
    $_SESSION['uc_notice_color'] = $color;
}

function set_last_site() {
    $_SESSION['uc_last'] = $_SESSION['uc_cur_url'];
    $_SESSION['uc_cur_url'] = ((empty($_SERVER['HTTPS'])) ? 'http' : 'https') .'://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function go_back() {
    header('Location: '.$_SESSION['uc_last']);
}

function go_home() {
    header('Location: ?admin=usercommander');
}

function secure_this_area() {
    if ($_GET['uc_sid'] != $_SESSION['uc_security']) {
        $_SESSION['uc_notice'] = 'Action denied, uc_sid is missing';
        $_SESSION['uc_notice_color'] = 'red';
        go_home();
    }
}

class CommandUser {

    private $db;
    private $log = "";
    public $work = false;

    public $user, $nick, $rlname, $pass, $level, $email, $city, $steamid, $skypename, $originid;

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
        $password = !empty($_POST['pass']) ? ", pwd ='".md5($_POST['pass'])."'" : '';
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
            $password." WHERE id = ".$_GET['id']);
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
            }
            else if($get == 'full_extinction'){
                $this->delete_from_user('newscomments',-1);
                $this->delete_from_user('forumposts',-1);
                $this->delete_from_user('acomments',-1);
                $this->delete_from_user('forumthreads',-1);
                $this->delete_from_user('messages',-1);
                $this->delete_from_user('cw_comments',-1);
                $this->delete_from_user('user',$this->id);
            } else {
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
            $this->work = true;
            $this->log("$get with ID: $id from user ".$this->nick." deleted");
        }
    }

    private function log($str) {
        $this->log .= "<tr><td>$str</td></tr>";
    }

    public function get_log_table() {
        return '<table>'.$this->log.'</table>';
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
        if ($get == 'full_extinction') $get = 'all';
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
        return show("admin/usercommander_show", array("show" => $table, "part" => $get, "username" => $this->user, "id" => -1));
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