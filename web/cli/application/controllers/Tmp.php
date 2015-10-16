<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tmp extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->output->enable_profiler(FALSE);
    }

    public function summary()
    {
//        $borrows = file(BASEPATH . '../cli/data/borrow.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $sql = 'SELECT `id`, `sn`,`title`,`amount`, `period`, `apr`, from_unixtime(`verified_time`) FROM t_borrow WHERE `add_apr` != ",," AND `add_apr` != "" AND `uid` = 0 AND `reverified_time` > 0 ORDER BY `reverified_time` ASC';
        $query = $this->db->query($sql);
        $borrows =  $query->result_array();
        foreach ($borrows as $borrow) {
            $sql = 'SELECT SUM(`recover_total_interest`) AS `total_interest` FROM `t_tender_log` WHERE `borrow_id`=' . (int) $borrow['id'];
            $query = $this->db->query($sql);
            $row = $query->row();
            if (!empty($row)) {
                $realpay = $row->total_interest;
            }
            else {
                $realpay = 0;
            }
            $payable = EqualDayEnd($borrow['period'], $borrow['amount'], $borrow['apr'], "all");
            $spread = bcsub($realpay, $payable['interest_total'], 3);
            echo join(',', $borrow) . ", {$payable['interest_total']}, {$realpay}, {$spread}\n";
        }
    }

    public function detail($sn)
    {
        $sql = 'SELECT `id`, `sn`,`title`,`amount`, `period`, `apr`, from_unixtime(`verified_time`) FROM t_borrow WHERE `sn` = "' . $sn . '"';
        $query = $this->db->query($sql);
        $borrow =  $query->row_array();
        $sql = 'SELECT `uid`, `capital`,`recover_total_interest`,`bag_id` FROM t_tender_log WHERE `borrow_id` = ' . (int) $borrow['id'];
        $query = $this->db->query($sql);
        $logs = $query->result_array();
//        echo join(',', $borrow) . "\n";
        foreach ($logs as $log) {
            $realpay = $log['recover_total_interest'];
            $payable = EqualDayEnd($borrow['period'], $log['capital'], $borrow['apr'], "all");
            $spread = bcsub($realpay, $payable['interest_total'], 3);
            $add_apr = $this->_get_add_apr($log['bag_id']);
            echo "{$log['uid']}, {$log['capital']}, {$add_apr}, {$payable['interest_total']}, {$realpay}, {$spread}\n";
        }
    }

    private function _get_add_apr($bid)
    {
        if ($bid <= 0) {
            return '未加息';
        }

        $this->config->load('props');
        $bag_cfg = $this->config->item('user_bag_type');

        $sql = 'SELECT `props_id` FROM `t_user_bag` WHERE `id` = ' . (int) $bid;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $bag_cfg[$row['props_id']]['name'];
    }
}
