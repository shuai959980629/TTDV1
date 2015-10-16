<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @ucenter用户中心
 * @author wangchuan
 * @category 2015-6-9
 * @version
 */

require_once(BASEPATH.'../com_party/libraries/ucenter/config.inc.php');
require_once(BASEPATH.'../com_party/libraries/ucenter/include/db_mysql.class.php');
//require_once(BASEPATH.'../com_party/libraries/ucenter/uc_client/client.php');
class Dcredit_model extends Base_model{
    public function __construct()
    {
        parent::__construct();
    }
    //根据用户名查询论坛积分
    public function get_user_credit($mobile){
        //$mobile = '18782963909';
        $db = new dbstuff;
        $db->connect(UC_DBHOST, UC_DBUSER,UC_DBPW, UC_DBNAME,0);

        $sql = "SELECT `uid` FROM ".DZ_DBTABLEPRE."common_member WHERE `username`={$mobile}" ;

        $result = $db->query($sql);

        if ($db->num_rows($result) == 0) {
            return false;
        }else{
            $row = $db->fetch_array($result);

            $sql = "SELECT * FROM ".DZ_DBTABLEPRE."common_member_count WHERE `uid`={$row['uid']}" ;

            $result = $db->query($sql);

            if ($db->num_rows($result) == 0) {
                return false;
            }else{
                $row = $db->fetch_array($result);
                return $row['extcredits2'];
            }
        }

    }

    //根据用户名扣除论坛积分
    public function update_user_credit($mobile, $credit){
        //$mobile = '18782963909';
        //$credit = 1;
        $db = new dbstuff;
        $db->connect(UC_DBHOST, UC_DBUSER,UC_DBPW, UC_DBNAME,0);

        $sql = "SELECT `uid` FROM ".DZ_DBTABLEPRE."common_member WHERE `username`={$mobile}" ;

        $result = $db->query($sql);

        if ($db->num_rows($result) == 0) {
            return false;
        }else{
            $row = $db->fetch_array($result);
            $uid = $row['uid'];

            $sql = "SELECT * FROM ".DZ_DBTABLEPRE."common_member_count WHERE `uid`={$uid}" ;

            $result = $db->query($sql);

            if ($db->num_rows($result) == 0) {
                return false;
            }else{
                $row = $db->fetch_array($result);
                if($row['extcredits2'] < $credit){
                    return false;
                }else{
                    //print_r($row);$row['extcredits2'] = 376;
                    $new_credit = $row['extcredits2'] - $credit;
                    $sql = 'UPDATE '.DZ_DBTABLEPRE."common_member_count SET extcredits2='{$new_credit}' WHERE uid='{$uid}'";
                    $re = $db->query($sql);
                    if ($db->num_rows($re) == 0) {
                        return false;
                    }else{
                        return true;
                    }
                }
            }
        }

    }
}
?>
