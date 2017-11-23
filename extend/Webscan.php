<?php
namespace app\extend;

class Webscan {
    private $webscan_switch; //拦截开关(1为开启，0关闭)
    private $webscan_white_directory; //后台白名单
    private $webscan_white_url; //url白名单
    private $webscan_get;
    private $webscan_post;
    private $webscan_cookie;
    private $webscan_referer;

    public function __construct ($webscan_switch = 1, $webscan_white_directory = '', $webscan_white_url = [], $webscan_get = 1, $webscan_post = 1, $webscan_cookie = 1, $webscan_referer = 1)
    {
        $this->webscan_switch = $webscan_switch;
        $this->webscan_white_directory = $webscan_white_directory;
        $this->webscan_white_url = $webscan_white_url;
        $this->webscan_get = $webscan_get;
        $this->webscan_post = $webscan_post;
        $this->webscan_cookie = $webscan_cookie;
        $this->webscan_referer = $webscan_referer;
    }

    // 参数拆分
    private function webscan_arr_foreach ($arr)
    {
        static $str;
        static $keystr;
        if ( !is_array ($arr) ) {
            return $arr;
        }
        foreach ( $arr as $key => $val ) {
            $keystr = $keystr . $key;
            if ( is_array ($val) ) {
                $this->webscan_arr_foreach ($val);
            } else {
                $str[] = $val . $keystr;
            }
        }

        return implode ($str);
    }

    // 攻击检查拦截
    private function webscan_StopAttack ($StrFiltKey, $StrFiltValue, $ArrFiltReq, $method)
    {
        $StrFiltValue = $this->webscan_arr_foreach ($StrFiltValue);
        if ( preg_match ("/" . $ArrFiltReq . "/is", $StrFiltValue) == 1 || preg_match ("/" . $ArrFiltReq . "/is", $StrFiltKey) == 1 ) {
            return TRUE;
        } else {
            return FALSE;
        }

    }

    // 拦截目录白名单
    private function webscan_white ($webscan_white_name, $webscan_white_url = [])
    {
        $url_path = $_SERVER[ 'SCRIPT_NAME' ];
        $url_var = $_SERVER[ 'QUERY_STRING' ];
        if ( preg_match ("/" . $webscan_white_name . "/is", $url_path) == 1 && !empty($webscan_white_name) ) {
            return FALSE;
        }
        foreach ( $webscan_white_url as $key => $value ) {
            if ( !empty($url_var) && !empty($value) ) {
                if ( stristr ($url_path, $key) && stristr ($url_var, $value) ) {
                    return FALSE;
                }
            } elseif ( empty($url_var) && empty($value) ) {
                if ( stristr ($url_path, $key) ) {
                    return FALSE;
                }
            }

        }

        return TRUE;
    }

    // 检测
    public function check ()
    {
        // get拦截规则
        $getfilter = "\\<.+javascript:window\\[.{1}\\\\x|<.*=(&#\\d+?;?)+?>|<.*(data|src)=data:text\\/html.*>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\(|benchmark\s*?\(.*\)|sleep\s*?\(.*\)|\\b(group_)?concat[\\s\\/\\*]*?\\([^\\)]+?\\)|\bcase[\s\/\*]*?when[\s\/\*]*?\([^\)]+?\)|load_file\s*?\\()|<[a-z]+?\\b[^>]*?\\bon([a-z]{4,})\s*?=|^\\+\\/v(8|9)|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.*\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)|UPDATE\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE)@{0,2}(\\(.+\\)|\\s+?.+?\\s+?|(`|'|\").*?(`|'|\"))FROM(\\(.+\\)|\\s+?.+?|(`|'|\").*?(`|'|\"))|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
        // post拦截规则
        $postfilter = "<.*=(&#\\d+?;?)+?>|<.*data=data:text\\/html.*>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\(|benchmark\s*?\(.*\)|sleep\s*?\(.*\)|\\b(group_)?concat[\\s\\/\\*]*?\\([^\\)]+?\\)|\bcase[\s\/\*]*?when[\s\/\*]*?\([^\)]+?\)|load_file\s*?\\()|<[^>]*?\\b(onerror|onmousemove|onload|onclick|onmouseover)\\b|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.*\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)|UPDATE\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE)(\\(.+\\)|\\s+?.+?\\s+?|(`|'|\").*?(`|'|\"))FROM(\\(.+\\)|\\s+?.+?|(`|'|\").*?(`|'|\"))|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
        // cookie拦截规则
        $cookiefilter = "benchmark\s*?\(.*\)|sleep\s*?\(.*\)|load_file\s*?\\(|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.*\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)|UPDATE\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE)@{0,2}(\\(.+\\)|\\s+?.+?\\s+?|(`|'|\").*?(`|'|\"))FROM(\\(.+\\)|\\s+?.+?|(`|'|\").*?(`|'|\"))|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
        // referer获取
        $referer = empty($_SERVER[ 'HTTP_REFERER' ]) ? [] : ['HTTP_REFERER' => $_SERVER[ 'HTTP_REFERER' ]];

        if ( $this->webscan_switch && $this->webscan_white ($this->webscan_white_directory, $this->webscan_white_url) ) {
            if ( $this->webscan_get ) {
                foreach ( $_GET as $key => $value ) {
                    if ( $this->webscan_StopAttack ($key, $value, $getfilter, "GET") ) return TRUE;
                }
            }
            if ( $this->webscan_post ) {
                foreach ( $_POST as $key => $value ) {
                    if ( $this->webscan_StopAttack ($key, $value, $postfilter, "POST") ) return TRUE;
                }
            }
            if ( $this->webscan_cookie ) {
                foreach ( $_COOKIE as $key => $value ) {
                    if ( $this->webscan_StopAttack ($key, $value, $cookiefilter, "COOKIE") ) return TRUE;
                }
            }
            if ( $this->webscan_referer ) {
                foreach ( $referer as $key => $value ) {
                    if ( $this->webscan_StopAttack ($key, $value, $postfilter, "REFERRER") ) return TRUE;
                }
            }

            return FALSE;
        }
    }
}

?>
