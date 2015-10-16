<?php
/**
 * 分页类
 * 
 * @package		CLASS
 * @author		LEE
 * @copyright	Copyright (c) 2015
 * @license		分页类 自定义
 * @link		...
 * @since		Version 2015-09-24
 * @filesource
 */
class Page
{
	/**
	 * 输出条数
	 *
	 * @var integer
	 */
	public $limit = 10;
	
	/**
	 * 分页链接 url前缀
	 *
	 * @var string
	 */
	public $prefix = 'page-';
	
	/**
	 * 分页链接 url后缀
	 *
	 * @var string
	 */
	public $suffix = '.html';

	/**
	 * 分页链接 A标签class名
	 *
	 * @var string
	 */
	public $style = 'current';
	
	/**
	 * 分页链接 显示最多链接地址
	 *
	 * @var integer
	 */
	public $point = 10;
	
	/**
	 * 设置皮肤
	 *
	 * @var string
	 */
	public $skin = "";
	
	/**
	 * 皮肤样式
	 *
	 * @var string
	 */
	public $active = "active";
	
	/**
	 * 定制分页
	 *
	 * @param  integer $page	//当前页码
	 * @param  integer $total	//数据总量
	 * @return string
	 */
	public function getPage($page, $total)
	{
		$prev = ceil($this->point / 2);
		$next = floor($this->point / 2);
		$num  = ceil($total / $this->limit);
		
		$page <= 0 && $page = 1;
		$page > $num && $page = $num;
		
		$_URL_PAGE = $jumper = $listStr = '';
		
		if ($num <= $this->point) {
			$n = 1;
			$m = $num;
		} else {
			if ($num - $page < $next) {
				$offset = $next - ($num - $page) - 1;
				$n = $page - $prev - $offset;
				$m = $num;
			} elseif ($page - $prev <= 0) {
				$n = 1;
				$m = $this->point;
			} else {
				$n = $page - $prev;
				$m = $n + $this->point - 1;
			}
		}
		
		for ($i = $n; $i <= $m; $i++) {
			if ($this->skin != "") {
				$listStr .= $i == $page 
					? '<' . $this->skin . ' class="' . $this->active . '"' . '><a href="javascript:;" class="'.$this->style.'">'.$i."</a>" . '</' . $this->skin . '>' 
					: '<' . $this->skin . '><a href="'.$this->prefix.$i.$this->suffix.'">'.$i."</a>" . '</' . $this->skin . '>';
			} else {
				$listStr .= $i == $page ? '<a href="javascript:;" class="'.$this->style.'">'.$i."</a>" : '<a href="'.$this->prefix.$i.$this->suffix.'">'.$i."</a>";
			}
		}
		
		if ($this->skin != "") {
			$page > 1 && $_URL_PAGE .= "<" . $this->skin . "><a href='" . $this->prefix . '1' . $this->suffix . "'>首页</a>" . "</" . $this->skin . ">";
			$page > 1 && $_URL_PAGE .= "<" . $this->skin . "><a href='" . $this->prefix . ($page - 1) . $this->suffix . "'>上一页</a>" . "</" . $this->skin . ">";
			$_URL_PAGE .= $listStr;
			($page + 1) <= $num && $_URL_PAGE .= "<" . $this->skin . "><a href='" . $this->prefix . ($page + 1) . $this->suffix . "'>下一页</a>" . "</" . $this->skin . ">";
			$num > 1 && $page  < $num && $_URL_PAGE .= "<" . $this->skin . "><a href='" . $this->prefix . $num . $this->suffix . "'>末页</a>" . "</" . $this->skin . ">";
		} else {
			$page > 1 && $_URL_PAGE .= "<a href='" . $this->prefix . '1' . $this->suffix . "'>首页</a>";
			$page > 1 && $_URL_PAGE .= "<a href='" . $this->prefix . ($page - 1) . $this->suffix . "'>上一页</a>";
			$_URL_PAGE .= $listStr;
			($page + 1) <= $num && $_URL_PAGE .= "<a href='" . $this->prefix . ($page + 1) . $this->suffix . "'>下一页</a>";
			$num > 1 && $page  < $num && $_URL_PAGE .= "<a href='" . $this->prefix . $num . $this->suffix . "'>末页</a>";
		}
		
		if ($this->skin == 'li') {
			$_URL_PAGE = "<ul class='pagination no-margin pull-right'>". $_URL_PAGE . "</ul>";
		}
		
		return $_URL_PAGE;
	}
	
	/**
	 * 数组分页 居然成功了
	 *
	 * @param  array $rule 	//分页数据、参数等
	 * 		例： array(
	 * 				'data'  => $data,	//所有总数据 二维数组
	 * 				'page'  => 1,		//当前页码
	 * 				'limit' => 20,		//读取条数
	 * 				'order' => array('time' => 'desc'),	//使用data里面的一个字段进行排序，必须在存在data二维数组里面
	 * 			)
	 * @return array
	 */
	public function getArrPage($rule)
	{
		if ( isset($rule['order']) && is_array($rule['order']) && !empty($rule['order']) ) {
			$field = key($rule['order']);
			$rank  = $rule['order'][$field] == 'asc' ? SORT_ASC : SORT_DESC;
			
			$order = array();
			foreach ( $rule['data'] as $key => $row ) {
				$order[$key]  = $row[$field];
			}	
			array_multisort($order, $rank,  $rule['data']);
		}
		
		$total  = count($rule['data']);
		$record = array_slice($rule['data'], ($rule['page'] - 1) * $this->limit, $this->limit);
		
		//调用定制分页类
    	$pageLinks = $this->getPage($rule['page'], $total);
    	
    	return array(
    		'record' 	=> $record,
    		'pageLink'	=> $pageLinks
    	);
	}
}
?>