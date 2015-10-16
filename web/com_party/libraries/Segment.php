<?php

/**
 * Created by PhpStorm.
 * User: geln
 * Date: 15-7-24
 * Time: 上午9:27
 */
class TrieException extends Exception {}
class SegmentException extends Exception {}
class TrieNodeContainerExctption extends Exception {}

class TrieNodeContainer implements ArrayAccess
{
    private $_data = array();

    private $_dir = '';

    public $cacheSize = 40;

    public function __construct($path = './data')
    {
        if (file_exists($path) === false) {
            throw new TrieNodeContainerExctption('字典目录不存在');
        }

        $this->_dir = realpath($path);

        if ($this->_dir === false) {
            throw new TrieNodeContainerExctption('字典目录不存在');
        }
    }

    public function __destruct()
    {
        foreach ($this->_data as $k => $v) {
            $this->save($k, $v);
        }

    }


    public function load($word)
    {
        $key = $this->_hash($word);
        if (!isset($this->_data[$key])) {
            $_file = "{$this->_dir}/{$key}.dat";
            if (file_exists($_file) === false) {
                return null;
            }

            $node = unserialize(file_get_contents($_file));

            if (count($this->_data) >= $this->cacheSize) {
                list($v, $k) = array(reset($this->_data), key($this->_data));
                unset($this->_data[$k]);
                $this->save($k, $v);
            }

            $this->_data[$key] = $node;
        }

        return  $this->_data[$key];
    }

    public function save($k, $v)
    {
        $_file = "{$this->_dir}/{$k}.dat";
        $result = file_put_contents($_file, serialize($v), LOCK_EX);
        return $result;
    }

    private function _hash($word)
    {
        $_len = mb_strlen($word, 'UTF-8');

        if ($_len != 1) {
            throw new TrieNodeContainerExctption('哈希关键字只允许一个字符');
        }

        return md5($word);
    }

    public function offsetExists ($offset)
    {
        $node = $this->load($offset);
        if ($node === null) {
            return false;
        }

        return true;
    }

    public function offsetGet ($offset)
    {
        $node = $this->load($offset);
        return $node;
    }

    public function offsetSet ($offset, $value)
    {
        $this->save($this->_hash($offset), $value);
//        $this->load($offset);
    }

    public function offsetUnset ($offset) {
        $k = $this->_hash($offset);
        $_file = "{$this->_dir}/{$k}.dat";
        if (file_exists($_file)) {
            @unlink($_file);
        }
        unset($this->_data[$k]);
    }

    private function _debug($msg)
    {
       echo "$msg\n";
    }
}

class TrieNode
{
    const CONTINUOUS = 0; //如果一个字符不是一个词条的尾字,称为继续状态
    const EXTENDED = 1; //如果一个字符是一个词条的尾字但该词还可以作为前缀构成一个更长的词语,称为延伸状态;
    const FINISHED = 2; //一个字符是一个词条的尾字且该词不可以作为前缀构成一个更长的词条,称为终结状态;

    public $entry = '';
    public $status = self::CONTINUOUS;
    public $children = array();
    public $depth = 0;

    public function __construct($depth = 0, $status = 0, $entry = '')
    {
        $this->status = $status;
        $this->depth = $depth;
        $this->entry = $entry;
    }
}

class Trie implements ArrayAccess
{
    private $_nodes = null;

    public function __construct($path = './data')
    {
        $this->_nodes = new TrieNodeContainer($path);
    }

    public function del($entry)
    {
        $entry = $this->preprocess($entry);
        $node = $this->find($entry);
        if ($node === null || $node->status == TrieNode::CONTINUOUS) {
           return false;
        }

        if ($node->status == TrieNode::EXTENDED) {
            $node->status = TrieNode::CONTINUOUS;
        }
        else {
            $prefix = mb_substr($entry, 0, (mb_strlen($entry, 'UTF-8') - 1), 'UTF-8');
            $last = mb_substr($entry, -1, 1, 'UTF-8');
            $node = $this->find($prefix);
            unset($node['children'][$last]);
        }

        return true;
    }

    public function find($entry)
    {
        $entry = $this->preprocess($entry);

        $node = null;
        $nodes = $this->_nodes;
        $len = mb_strlen($entry, 'UTF-8');
        $pos = 0;

        do
        {
            $word = mb_substr($entry, $pos, 1, 'UTF-8');
            if( isset($nodes[$word]) && !empty($nodes[$word]) )
            {
                $pos++;
                $node = $nodes[$word];
                $nodes = $node->children;
            }
            else {
                break;
            }

        } while( $node !== null && $pos < $len );

        return $node;
    }

    public function insert($entry)
    {
        $entry = $this->preprocess($entry);

        $parent = $this->find($entry);
        $len = mb_strlen($entry, 'UTF-8');

        if ($parent === null) {
            $first = mb_substr($entry, 0, 1, 'UTF-8');
            $this->_nodes[$first] = new TrieNode();
            $parent = $this->_nodes[$first];
        }
        else {
            $parent->status = $parent->status == TrieNode::FINISHED ? TrieNode::EXTENDED : $parent->status;
        }

        for ($i = ($parent->depth + 1); $i < $len; $i++) {
            $word = mb_substr($entry, $i, 1, 'UTF-8');
            $node = new TrieNode($i);
            $parent->children[$word] = $node;
            $parent = $node;
        }

        $parent->status = TrieNode::FINISHED;
        $parent->entry = $entry;

        return $parent;
    }

    private function preprocess($entry)
    {
        $entry = trim(preg_replace('/\s\s+/', '', $entry));

        if (empty($entry))
        {
            throw new TrieException('空字符串要求不得');
        }

        return $entry;
    }

    public function offsetExists ($offset)
    {
        $node = $this->find($offset);
        if (
            $node === null ||
            $node->status == TrieNode::CONTINUOUS ||
            $node->entry != $offset
        ) {
            return false;
        }

        return true;
    }

    public function offsetGet ($offset)
    {
        $node = $this->find($offset);
        if (
            $node === null ||
            $node->status == TrieNode::CONTINUOUS ||
            $node->entry != $offset
        ) {
            return null;
        }

        return $node;
    }

    public function offsetSet ($offset, $value)
    {
        $this->insert($offset);
    }

    public function offsetUnset ($offset) {
        $this->del($offset);
    }
}

class Segment
{
    public $trie = null;

    public function __construct($dict = './dict')
    {
        $dict = empty($dict) ? './dict' : $dict;

        if (is_dir($dict) === false) {
            throw new SegmentException('哟，找不到词典，分毛啊');
        }

        $this->trie = new Trie($dict);
    }

    public function cut($txt)
    {
        $sections = $this->shelling($txt);
        $entries = array();
        foreach ($sections as $section) {
            $len = mb_strlen($section, 'UTF-8');
            for ($i = 0; $i < $len; $i++) {
                $entry = mb_substr($section, $i, null, 'UTF-8');
                try {
                    $node = $this->trie->find($entry);
                } catch (TrieException $e) {
                    continue;
                }
                if (!empty($node) && $node->status != TrieNode::CONTINUOUS) {
                    $entries[] = $node->entry;
                    $i += mb_strlen($node->entry, 'UTF-8') - 1;
                }
            }
        }
        return $entries;
    }

    public function shelling($txt)
    {
        mb_regex_encoding('UTF-8');
        $lines = mb_split('[\.\。\?\!\！\？\,\，\“\”\：\…\n\s]+', $txt);
        foreach ($lines as $k => $v) {
            $v = trim($v);
            if (empty($v)) {
                unset($lines[$k]);
            }
            else {
                $lines[$k] = $v;
            }
        }

        return $lines;
    }
}
