<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Borrow_watermark
{
    public function work($data)
    {
        $CI = & get_instance();
        $CI->load->library('image_lib');
        foreach($data as $v){
            //居中水印
            $config['image_library'] = 'GD2';
            $config['wm_type'] = 'overlay';
            $config['source_image'] = realpath(FCPATH.'/data/'.$v);
            $config['wm_overlay_path'] = realpath(FCPATH. '/data/watermark/watermark_zz.png');
            $config['wm_vrt_alignment'] = 'middle';
            $config['wm_hor_alignment'] = 'center';

            $config['wm_hor_offset'] = 0;
            $config['wm_vrt_offset'] = 0;

            $CI->image_lib->initialize($config);

            $result = $CI->image_lib->watermark();

            //右上角水印
            $config['image_library'] = 'GD2';
            $config['wm_type'] = 'overlay';
            $config['source_image'] = realpath(FCPATH. '/data/'.$v);
            $config['wm_overlay_path'] = realpath(FCPATH.'/data/watermark/watermark_shipai.png');
            $config['wm_vrt_alignment'] = 'top';
            $config['wm_hor_alignment'] = 'right';

            $config['wm_hor_offset'] = 70;
            $config['wm_vrt_offset'] = 70;

            $CI->image_lib->initialize($config);


            $result = $CI->image_lib->watermark();

            //右下角水印
            $config['image_library'] = 'GD2';
            $config['wm_type'] = 'overlay';
            $config['source_image'] = realpath(FCPATH.'/data/'.$v);
            $config['wm_overlay_path'] = realpath(FCPATH.'/data/watermark/watermark_logo.png');
            $config['wm_vrt_alignment'] = 'bottom';
            $config['wm_hor_alignment'] = 'right';

            $config['wm_hor_offset'] = 70;
            $config['wm_vrt_offset'] = 70;

            $CI->image_lib->initialize($config);

            $result = $CI->image_lib->watermark();
        }
    }
}
