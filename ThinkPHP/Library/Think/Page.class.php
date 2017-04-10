<?php
/**
 * Copyright (c) 2017
 * Created by PhpStorm.
 * 文件: page.php
 * 摘要: 此分页类样式已经写好,直接引用即可
 * 作者: 刘亚博
 * CreateTime:2017/3/14 15:19
 * $Id$
 * 
 * 使用方法:
 *          在模板中引入样式  <link rel="stylesheet" type="text/css" href="Page.class.css">
            //分页
            require_once 'Page.class.php';
            $page_size = 5;
            $page = isset($_GET['page']) ? $_GET['page'] : 1;
            $start = ($page-1) * $page_size;
            $where = "";
            $rows = 1000;
            $url = "?page={page}";
            $page_model = new Page($rows, $page_size, $page, $url, 2);
            $page_nav = $page_model->page_show();
 * 
 */
namespace Think;
header("Content-type:text/html;charset=utf-8");
class Page {
    
    private $_total;          //总记录数
    private $_size;           //一页显示的记录数
    private $_page;           //当前页
    private $_page_count;     //总页数
    private $_i;              //起头页数
    private $_en;             //结尾页数
    private $_url;            //获取当前的url
    /*
     * $show_pages
     * 页面显示的格式，显示链接的页数为2*$show_pages+1。
     * 如$show_pages=2那么页面上显示就是[首页] [上页] 1 2 3 4 5 [下页] [尾页]
     */
    private $show_pages;

    public function __construct($_total = 1, $_size = 1, $_page = 1, $_url, $show_pages = 2) {
        $this->_total = $this->numeric($_total);
        $this->_size = $this->numeric($_size);
        $this->_page = $this->numeric($_page);
        $this->_page_count = ceil($this->_total / $this->_size);
        $this->_url = $_url;
        if ($this->_total < 0)
            $this->_total = 0;
        if ($this->_page < 1)
            $this->_page = 1;
        if ($this->_page_count < 1)
            $this->_page_count = 1;
        if ($this->_page > $this->_page_count)
            $this->_page = $this->_page_count;
        $this->limit = ($this->_page - 1) * $this->_size;
        $this->_i = $this->_page - $show_pages;
        $this->_en = $this->_page + $show_pages;
        if ($this->_i < 1) {
            $this->_en = $this->_en + (1 - $this->_i);
            $this->_i = 1;
        }
        if ($this->_en > $this->_page_count) {
            $this->_i = $this->_i - ($this->_en - $this->_page_count);
            $this->_en = $this->_page_count;
        }
        if ($this->_i < 1)
            $this->_i = 1;
    }

    //检测是否为数字
    private function numeric($num) {
        if (strlen($num)) {
            if (!preg_match("/^[0-9]+$/", $num)) {
                $num = 1;
            } else {
                $num = substr($num, 0, 11);
            }
        } else {
            $num = 1;
        }
        return $num;
    }

    //地址替换
    private function page_replace($page) {
        return str_replace("{page}", $page, $this->_url);
    }

    //首页
    private function _home() {
        if ($this->_page != 1) {
            return "<a href='" . $this->page_replace(1) . "' title='首页'>首页</a>";
        } else {
            return "<p>首页</p>";
        }
    }

    //上一页
    private function _prev() {
        if ($this->_page != 1) {
            return "<a href='" . $this->page_replace($this->_page - 1) . "' title='上一页'>上一页</a>";
        } else {
            return "<p>上一页</p>";
        }
    }

    //下一页
    private function _next() {
        if ($this->_page != $this->_page_count) {
            return "<a href='" . $this->page_replace($this->_page + 1) . "' title='下一页'>下一页</a>";
        } else {
            return"<p>下一页</p>";
        }
    }

    //尾页
    private function _last() {
        if ($this->_page != $this->_page_count) {
            return "<a href='" . $this->page_replace($this->_page_count) . "' title='尾页'>尾页</a>";
        } else {
            return "<p>尾页</p>";
        }
    }

    //输出
    public function page_show($id = 'page') {
        $str = "<div id=" . $id . ">";
        $str.=$this->_home();
        $str.=$this->_prev();
        if ($this->_i > 1) {
            $str.="<p class='pageEllipsis'>...</p>";
        }
        for ($i = $this->_i; $i <= $this->_en; $i++) {
            if ($i == $this->_page) {
                $str.="<a href='" . $this->page_replace($i) . "' title='第" . $i . "页' class='cur'>$i</a>";
            } else {
                $str.="<a href='" . $this->page_replace($i) . "' title='第" . $i . "页'>$i</a>";
            }
        }
        if ($this->_en < $this->_page_count) {
            $str.="<p class='pageEllipsis'>...</p>";
        }
        $str.=$this->_next();
        $str.=$this->_last();
        $str.="<p class='pageRemark'>共<b>" . $this->_page_count .
            "</b>页<b>" . $this->_total . "</b>条数据</p>";
        $str.="</div>";
        return $str;
    }

}

?>