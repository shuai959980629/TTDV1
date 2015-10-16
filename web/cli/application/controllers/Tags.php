<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tags extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->output->enable_profiler(FALSE);

        $this->load->model('tags_model', 'tags');
        $this->load->model('article_tag_model', 'artilce_rel');
        $this->load->model('tags_rel_model', 'tag_rel');
        $this->load->model('article_model', 'article');
    }

    public function _remap($method, $params = array())
    {
        if (method_exists($this, $method))
        {
            return call_user_func_array(array($this, $method), $params);
        }
        $this->help();
    }

    public function help()
    {
        echo <<<EOF
Usage:
index.php 操作 <其它参数>

help:显示帮助说明
import:导入关键词
gen_dict:重新生成分词字典文件
rebulid_article_rel:重新建立文章与标签的关联
rebulid_tag_rel:计算标签之间的相似度

EOF;
    }

    public function import($wf)
    {
        if (file_exists($wf) === FALSE) {
            $this->_error("文件：{$wf}, 不存在！");
            return FALSE;
        }

        $words = file($wf, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $words = array_unique($words);

        if (count($words) == 0) {
            $this->_error("没有需要导入的关键词！");
            return FALSE;
        }

        $this->load->helper('pinyin');

        print_r("开始执行导入, 共" . count($words) . "个关键词\n");

        $result = 0;
        foreach ($words as $word) {
            $alpha = ucwords(get_pinyin(mb_substr($word, 0, 1, 'UTF8'), TRUE));
            $entry = array(
                'keyword' => $word,
                'alpha'   => $alpha == '_' ? 'X' : $alpha
            );
            $this->db->replace('article_tags', $entry) && $result++;

            if ($result <= 0) {
                $this->_error('导入操作失败，请检查文件后，重试');
            }
        }

        print_r("导入完成, 成功导入" . count($words) . "个关键词\n");
    }

    public function gen_dict()
    {
        require_once(BASEPATH.'../com_party/libraries/Segment.php');

        $path = APPPATH . '../data/dict';

        if (!file_exists($path)) {
            $this->_error('指定的字典目录不存在，开始创建目录');
            if (mkdir($path, 0774, TRUE) === FALSE) {
                $this->_error('创建字典目录失败，请检查后重试');
                return FALSE;
            }
        }

        $trie = new Trie($path);

/*        $entries = $this->tags->search(
            array(
                'keyword' => '月'
            )
        );*/

       $entries = $this->tags->all(
            array(
            ),
           array(
               'keyword'
           )
        );

        foreach ($entries as $entry) {
            $trie[$entry['keyword']] = $entry['keyword'];
        }
    }

    public function rebulid_article_rel($cat_id = 0)
    {
        $cat_id = (int) $cat_id;
        if ($cat_id > 0) {
            $articles = $this->article->all(
                array(
                    'cat_id' => $cat_id
                ),
                array(
                    'id',
                    'title',
                    'content'
                )
            );
        }
        else {
            $articles = $this->article->all(
                array(),
                array(
                    'id',
                    'title',
                    'content'
                )
            );
        }

        if (count($articles) == 0) {
            $this->_error("没有需要处理的资讯！");
            return FALSE;
        }

        print_r("开始重建资讯标签关联, 共" . count($articles) . "篇资讯\n");

        foreach ($articles as $artilce) {
            try {
                $keywords = $this->_segment($artilce['content']);
            } catch (SegmentException $e) {
                print_r("warning: {$artilce['title']}，" . $e->getMessage() . "\n");
                continue;
            }

            $result = $this->article->update(
                $artilce['id'],
                array('tags' => join(',', $keywords))
            );

            print_r("{$artilce['title']}：" . join(',', $keywords) . "\n");

            if ($result) {
                $this->artilce_rel->build_rel($artilce['id'], $keywords);
            }
        }
    }

    private function _error($msg)
    {
        print_r("$msg\n");
    }

    public function _segment($text)
    {
        require_once(BASEPATH.'../com_party/libraries/Segment.php');

        $text = strip_tags($text);

        if (empty($text)) {
            throw new SegmentException('正文内容不能为空');
        }

        $path = APPPATH . '../data/dict';
        try {
            $segment = new Segment($path);
            $keywords = array_values(array_unique($segment->cut($text)));
        } catch (SegmentException $e) {
            throw $e;
        }

        return $keywords;
    }

    public function rebulid_tag_rel($citations = 1)
    {
        $citations = (int) $citations;
        $citations = $citations <= 0 ? 1 : $citations;

        $tags = $this->tags->all(
            array('citations' => $citations),
            array('id', 'keyword')
        );

        print_r("开始计算标签之间的相似度, 共" . count($tags) . "个标签\n");

        foreach ($tags as $tag) {
            for ($i = 0; $i < count($tags); $i++) {

                if ($tag['keyword'] == $tags[$i]['keyword']) {
                    continue;
                }

                if ($this->tag_rel->is_exists($tag['id'], $tags[$i]['id'])) {
                    continue;
                }

                $similarity = $this->_similar($tag['keyword'], $tags[$i]['keyword']);

                if ($similarity <= 0) {
                    continue;
                }

                $data = array(
                    'from_tag_id' => $tag['id'],
                    'to_tag_id' => $tags[$i]['id'],
                    'similarity' => $similarity,
                );

                print_r("{$tag['keyword']}<———>{$tags[$i]['keyword']}：{$data['similarity']}%\n");

                $this->tag_rel->create($data);
            }
        }
    }

    private function _similar($s1, $s2)
    {
        $chars1 = preg_split('//u', $s1, -1, PREG_SPLIT_NO_EMPTY);
        $chars2 = preg_split('//u', $s2, -1, PREG_SPLIT_NO_EMPTY);

        $chars3 = array_intersect($chars1, $chars2);

        $similarity = round(count($chars3) * 2 / (count($chars1) + count($chars2)), 2) * 100;

        return $similarity;
    }
}
