<?php
	namespace QL\Ext;
	
	use QL\Contracts\PluginContract;
	use QL\QueryList;
	
	/**
	 *  360问答搜索插件
	 * Created by Malcolm.
	 * Date: 2021/9/7  10:29 上午
	 */
	class SoWenDa
	{
		
		const API = 'https://wenda.so.com/search/';
		const RULES = [
			'title' => [ 'h3 a' , 'text' ] ,
			'link'  => [ 'h3 a' , 'href' ]
		];
		const RANGE = '.qa-i-hd';
		protected $ql;
		protected $keyword;
		protected $pageNumber = 10;
		protected $httpOpt = [
			'headers' => [
				'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36' ,
				'Accept-Encoding' => 'gzip, deflate, br' ,
				'Referer'         => 'https://www.so.com' ,
				'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9' ,
				'Accept-Language' => 'en-US,en;q=0.9,zh-CN;q=0.8,zh;q=0.7'
			]
		];
		
		public function __construct ( QueryList $ql , $pageNumber ) {
			$this->ql = $ql->rules( self::RULES )
			               ->range( self::RANGE );
			$this->pageNumber = $pageNumber;
		}
		
		public static function install ( QueryList $queryList , ...$opt ) {
			$name = $opt[0] ?? 'wenda360';
			$queryList->bind( $name , function ( $pageNumber = 10 )
			{
				return new SoWenDa( $this , $pageNumber );
			} );
		}
		
		public function setHttpOpt ( array $httpOpt = [] ) {
			$this->httpOpt = $httpOpt;
			return $this;
		}
		
		public function search ( $keyword ) {
			$this->keyword = $keyword;
			return $this;
		}
		
		public function page ( $page = 1 , $realURL = false ) {
			return $this->query( $page )
			            ->query()
			            ->getData( function ( $item ) use ( $realURL )
			            {
				            if ( isset( $item['title'] ) && $item['title'] ) {
					            $encode = mb_detect_encoding( $item['title'] , array( "ASCII" , 'UTF-8' , "GB2312" , "GBK" , 'BIG5' ) );
					            $item['title'] = iconv( $encode , 'UTF-8' , $item['title'] );
				            }
				            $realURL && $item['link'] = $this->getRealURL( $item['link'] );
				            return $item;
			            } );
		}
		
		protected function query ( $page = 1 ) {
			$this->ql->get( self::API , [
				'q' => $this->keyword ,
				'pn'   => $page-1
			] , $this->httpOpt );
			return $this->ql;
		}
		
		/**
		 * 得到真正地址
		 * @param $url
		 * @return mixed
		 */
		protected function getRealURL ( $url ) {
			if ( empty( $url ) ) {
				return $url;
			}
			
			return 'https://wenda.so.com'.$url;
		}
		
		public function getCountPage () {
			$count = $this->getCount();
			return ceil( $count / $this->pageNumber );
		}
		
		public function getCount () {
			$text = $this->query( 1 )
			             ->find( '#qaresult-page .last' )
			             ->href();
			
			$tmp = explode('=',$text);
			
			$count = end($tmp);
			
			return (int) $count;
		}
		
	}