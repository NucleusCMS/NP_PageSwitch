<?php
class NP_PageSwitch extends NucleusPlugin { 
	function getName() { return 'NP_PageSwitch'; }
	function getMinNucleusVersion() { return 330; }
	function getAuthor()  { return 'Katsumi'; }
	function getVersion() { return '1.1.5'; }
	function getURL() {return 'http://japan.nucleuscms.org/bb/viewtopic.php?t=3295';}
	function getDescription() { return $this->getName().' plugin'; } 
	function supportsFeature($what) { return (int)($what=='SqlTablePrefix'); }
	function install() {
		$this->createOption('multicat','Use Multiple Categories?','yesno','no');
	}
	var $limit=10;
	function doSkinVar($skinType,$type,$p1='') {
		global $startpos;
		$pos=isset($startpos)?(int)$startpos:0;
		$limit=$this->limit;
		switch($type=strtolower($type)){
		case 'limit':
			$this->limit=(int)$p1;
			return;
		case 'info':
			if ($limit<$this->getTotal()) echo htmlspecialchars($p1,ENT_QUOTES);
			return;
		case 'num':
			echo intval($startpos/$limit)+1;
			return;
		case 'total':
			echo intval(($this->getTotal()-1)/$limit)+1;
			return;
		case 'found':
			echo intval($this->getTotal());
			return;
		case 'prev':
			if ($limit<=$pos) echo '<a href="'.htmlspecialchars($this->url($pos-$limit)).'">'.htmlspecialchars($p1).'</a>';
			return;
		case 'next':
			if ( $pos+$limit<$this->getTotal() ) echo '<a href="'.htmlspecialchars($this->url($pos+$limit)).'">'.htmlspecialchars($p1).'</a>';
			return;
		case 'index':
			$tags=array();
			$num=intval(($this->getTotal()-1)/$limit)+1;
			$around=false;
			for ($i=0;$i<$num;$i++) {
				if ($p1) {
					if ($i-1<=$pos/$limit && $pos/$limit<=$i+1) {
						$around=true;
					} else {
						if (($p1==$i && $i<$num-$p1 || $around && $p1<=$i && $i==$num-$p1) 
							&& $tags[count($tags)-1]!='&nbsp;...&nbsp;') $tags[]='&nbsp;...&nbsp;';
						if ($p1<=$i && $i<$num-$p1) continue;
					}
					
				}
				if ($i==$pos/$limit) $tags[]='<b>'.(string)($i+1).'</b>';
				else $tags[]='<a href="'.htmlspecialchars($this->url($i*$limit)).'">'.($i+1).'</a>';
			}
			echo implode(',',$tags);
			return;
		default: break;
		}
	}
	var $multi=false;
	var $total;
	function getTotal(){
		global $blog,$query,$amount;
		if (isset($this->total)) return $this->total;
		global $blog,$catid,$subcatid;
		$scid=isset($subcatid)?(int)$subcatid:0;
		if ($query) {
			$highlight='';
			$sqlquery = $blog->getSqlSearch($query, $amount, $highlight,'count');
			$this->total=(int)quickQuery($sqlquery);
		} elseif ( $scid && $this->getOption('multicat')=='yes') {
			$sqlquery = $blog->getSqlBlog('AND i.inumber=p.item_id AND p.subcategories REGEXP "(^|,)'.(int)$scid.'(,|$)" ','count');
			$sqlquery = preg_replace('/^([\s]*)SELECT[\s]([^\'"=]*)[\s]FROM[\s]/i',
				'SELECT COUNT(*) as result FROM '.sql_table('plug_multiple_categories').' as p, ',$sqlquery);
			$this->total=(int)quickQuery($sqlquery);
		} elseif ($catid && $this->getOption('multicat')=='yes') {
			$sqlquery = $blog->getSqlBlog('AND i.inumber=p.item_id AND p.categories REGEXP "(^|,)'.(int)$catid.'(,|$)" '
				.'AND NOT i.icat='.(int)$catid.' ','count');
			$sqlquery = preg_replace('/^([\s]*)SELECT[\s]([^\'"=]*)[\s]FROM[\s]/i',
				'SELECT COUNT(*) as result FROM '.sql_table('plug_multiple_categories').' as p, ',$sqlquery);
			$this->total=(int)quickQuery($sqlquery);
			$sqlquery = $blog->getSqlBlog('AND i.icat='.(int)$catid.' ','count');
			$sqlquery = preg_replace('/^([\s]*)SELECT[\s]([^\'"=]*)[\s]FROM[\s]/i','SELECT COUNT(*) as result FROM ',$sqlquery);
			$this->total+=(int)quickQuery($sqlquery);
		} else {
			$case=$catid?'AND i.icat='.(int)$catid.' ':'';
			$sqlquery = $blog->getSqlBlog($case,'count');
			$this->total=(int)quickQuery($sqlquery);
		}
		return $this->total;
	}
	function url($pos){
		global $HTTP_SERVER_VARS;
		$qs=isset($_SERVER)?$_SERVER['QUERY_STRING']:$HTTP_SERVER_VARS['QUERY_STRING'];
		if (!strstr($qs,'startpos=')) $qs.=($qs?'&':'').'startpos=0';
		return '?'.preg_replace('/startpos=([0-9]+)/i','startpos='.$pos,$qs);
	}
	function doIf($p1='',$p2=''){
		switch($p1=strtolower($p1)){
		case 'limit':
			$this->limit=(int)$p2;
		case 'required':
		default:
			return $this->limit<$this->getTotal();
		}
	}
}

?>