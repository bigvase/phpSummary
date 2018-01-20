<?php
import("ORG.Util.Page");
class MyPaging extends Page{
	
    function __construct($totalRows,$listRows='',$parameter='',$url='') {
        	parent::__construct($totalRows,$listRows,$parameter,$url);
        	$this->totalRows    =   $totalRows;
        	$this->parameter    =   $parameter;
        	$this->varPage      =   C('VAR_PAGE') ? C('VAR_PAGE') : 'p' ;
        	if(!empty($listRows)) {
        		$this->listRows =   intval($listRows);
        	}
        	$this->totalPages   =   ceil($this->totalRows/$this->listRows);     //总页数
        	$this->coolPages    =   ceil($this->totalPages/$this->rollPage);
			$vp = I($this->varPage);
        	$this->nowPage      =   !empty($vp)?intval($vp):1;
        	if($this->nowPage<1){
        		$this->nowPage  =   1;
        	}elseif(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
        		$this->nowPage  =   $this->totalPages;
        	}
        	$this->firstRow     =   $this->listRows*($this->nowPage-1);
        	if(!empty($url))    $this->url  =   $url;
    }

    function getNowPage(){
        return $this->nowPage;
    }
    
    /**
     * 分页显示输出
     * @access public
     */
    public function show() {
        if(0 == $this->totalRows) return '';
        $p              =   $this->varPage;
        $nowCoolPage    =   ceil($this->nowPage/$this->rollPage);
        // 分析分页参数
        if($this->url){
            $depr       =   C('URL_PATHINFO_DEPR');
            $url        =   rtrim(U('/'.$this->url,'',false),$depr).$depr.'__PAGE__';
        }else{
            if($this->parameter && is_string($this->parameter)) {
                parse_str($this->parameter,$parameter);
            }elseif(is_array($this->parameter)){
                $parameter      =   $this->parameter;
            }elseif(empty($this->parameter)){
                unset($_GET[C('VAR_URL_PARAMS')]);
                $var =  I('request.');
                if(empty($var)) {
                    $parameter  =   array();
                }else{
                    $parameter  =   $var;
                }
            }
            $parameter[$p]  =   '__PAGE__';
            $url            =   U('',$parameter);
            $url = htmlspecialchars($url);
        }
        //上下翻页字符串
        $upRow          =   $this->nowPage-1;
        $downRow        =   $this->nowPage+1;
        if ($upRow>0){
            $upPage     =   "<a href='".str_replace('__PAGE__',$upRow,$url)."'>".$this->config['prev']."</a>";
        }else{
            $upPage     =   '';
        }

        if ($downRow <= $this->totalPages){
            $downPage   =   "<a href='".str_replace('__PAGE__',$downRow,$url)."'>".$this->config['next']."</a>";
        }else{
            $downPage   =   '';
        }
        // << < > >>
        if($nowCoolPage == 1){
            $theFirst   =   '';
            $prePage    =   '';
        }else{
            $preRow     =   $this->nowPage-$this->rollPage;
            $prePage    =   "<a href='".str_replace('__PAGE__',$preRow,$url)."' >上".$this->rollPage."页</a>";
            $theFirst   =  "<a href='".str_replace('__PAGE__',1,$url)."' >".$this->config['first']."</a>";
        }
        
        
        if($this->nowPage == $this->totalPages){
            $nextPage   =   '';
            $theEnd     =   '';
        }else{
            $nextRow    =   $this->nowPage+$this->rollPage;
            $theEndRow  =   $this->totalPages;
            $nextPage   =   "<a href='".str_replace('__PAGE__',$nextRow,$url)."' >下".$this->rollPage."页</a>";
            $endem = ($this->nowPage+3) >= $this->totalPages ? "":"<em>…</em>";
            $theEnd     =   $endem."<a href='".str_replace('__PAGE__',$theEndRow,$url)."' >".$this->config['last']."</a>";
        }
        // 1 2 3 4 5
        $linkPage = "";
        
        $firstem = ($this->nowPage-3) > 1 ? "<em>…</em>":"";
        
        if($this->totalPages != 1){
	        if($this->nowPage == 1){
	        	$linkPage .= "<span class='current'>1</span>";
	        }else{
	        	$linkPage .= "<a href='".str_replace('__PAGE__',1,$url)."'>1</a>".$firstem;
	        }
        }
        
        $endPage = ($this->nowPage+2) < $this->totalPages ?  ($this->nowPage+2):$this->totalPages;
        $startPage = ($this->nowPage-2) > 0 ?  ($this->nowPage-2):1;
        for($i=$startPage;$i<=$endPage;$i++){
        	if($i == 1) continue;
            $page       = $i;
            if($page!=$this->nowPage){
                if($page < $this->totalPages){
                    $linkPage .= "<a href='".str_replace('__PAGE__',$page,$url)."'>".$page."</a>";
                }else{
                    break;
                }
            }else{
                if($this->totalPages != 1){
                    $linkPage .= "<span class='current'>".$page."</span>";
                }
            }
        }
        $pageStr     =   str_replace(
            array('%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%'),
            array($this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd),
                $this->config['theme']);
        return $pageStr;
    }
    
}