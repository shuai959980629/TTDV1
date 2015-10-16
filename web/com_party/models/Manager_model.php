<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Manager_model extends Base_model {
    public $status    = array(
        'allow' => '允许',
        'deny'  => '禁止'
    );

    public $sex = array(
        'unknown' => '保密',
        'male'    => '男',
        'female'  => '女'
    );

    public $ERROR_USER_UNKNOWN   = -1; //查找不到此用户;
    public $ERROR_USER_DENY      = -2; //帐号被冻结
    public $ERROR_WRONG_PASSWORD = -3; //密码不匹配
    public $SUCCESS_ID           = 1; //登录成功

    public function __construct()
    {
        parent::__construct('manager');

        $this->pk = 'uid';
    }

    public function auth($username, $password)
    {
        $user = $this->get_user_by_name($username);
        if (empty($user)) {
            return $this->ERROR_USER_UNKNOWN;
        }

        $is_allow = ($user['status'] == 'allow');
        if (!$is_allow) {
            return $this->ERROR_USER_DENY;
        }

        $is_matche = $user['password'] == $this->mix_passwd($this->input->post('password'), $user['salt']);
        if (!$is_matche) {
            return $this->ERROR_WRONG_PASSWORD;
        }

        return $this->SUCCESS_ID;
    }

    public function get_user_by_name($username)
    {
        $where = array(
            'username' => $username
        );
        $query = $this->db->get_where($this->table, $where);
        return $query->row_array();
    }

    public function get_user_by_id($uid)
    {
        $where = array(
            'uid' => (int) $uid
        );

        $query = $this->db->get_where($this->table, $where);
        return $query->row_array();
    }

    public function change_password($uid, $password, $new_password)
    {
        $user = $this->get_user_by_id($uid);
        if (is_null($user)) {
            return FALSE;
        }

        $this->load->helper('string');
        $salt = random_string('alnum', 6);
        $data = array(
            'password'  => $this->mix_passwd($new_password, $salt),
            'salt'      => $salt,
        );

        $this->db->where('uid', $uid)
            ->where('password', $this->mix_passwd($password, $user['salt']))
            ->update($this->table, $data);

        if ($this->db->affected_rows() > 0) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    public function mix_passwd($password, $salt)
    {
        return md5(md5($password) + $salt);
    }

    public function create($data)
    {
        $this->load->helper('string');
        $salt = random_string('alnum', 6);
        $data = array(
            'password'  => $this->mix_passwd($data['password'], $salt),
            'salt'      => $salt,
        ) + $data;

        $this->db->insert($this->table, $data);

        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }
        else {
            return 0;
        }
    }

    public function remove($id)
    {
        $result = parent::remove($id);
        if ($result) {
            $this->load->model('admin_log_model', 'admin_log');
            foreach ($id as $_id) {
                $this->admin_log->clear($_id);
            }
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    public function update($id, $data)
    {
        if (!empty($data['password'])) {
            $this->load->helper('string');
            $salt = random_string('alnum', 6);
            $data = array(
                'password'  => $this->mix_passwd($data['password'], $salt),
                'salt'      => $salt,
            ) + $data;
        }
        else {
            unset($data['password']);
        }

        $this->db->where('uid', (int) $id)
            ->update($this->table, $data);

        if ($this->db->affected_rows() > 0) {
            return $id;
        }
        else {
            return FALSE;
        }
    }

    public function get_user_by_role($role)
    {
        return $this->all(array('role' => $role));
    }

    public function _where($where = array())
    {
        if (isset($where['role']) && !empty($where['role'])) {
            $this->db->where('role', $where['role']);
        }

        if (isset($where['status']) && !empty($where['status'])) {
            $this->db->where('status', $where['status']);
        }

        if (isset($where['name']) && !empty($where['name'])) {
            $this->db->like('realname', $where['name']);
            $this->db->or_like('username', $where['name']);
        }

        if (isset($where['phone']) && !empty($where['phone'])) {
            $this->db->like('phone', $where['phone']);
        }

        if (isset($where['deny_root'])) {
            $this->db->where('uid !=', 1);
        }
    }
}
