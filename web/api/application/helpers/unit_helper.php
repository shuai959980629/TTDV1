<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 *
 * @package		Libraries
 * @author		Glen.luo
 * @since		Version 1.0
 */

// ------------------------------------------------------------------------

/**
 * 生成唯一主键
 *
 * @access	public
 * @param	null
 * @return	int 63bits
 */
if ( ! function_exists('unique_id'))
{
    function unique_id()
    {
        $CI        = & get_instance();

        $tickets_db = $CI->load->database('tickets', TRUE);

        $query = $tickets_db->query('REPLACE INTO tickets64 (stub) VALUES ("a")');

        $ticket_id = NULL;

        if ($query) {
            $ticket_id = $tickets_db->insert_id();
        }

        $tickets_db->close();
        return $ticket_id;
    }
}