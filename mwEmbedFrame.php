<?
/**
 * mwEmbedFrame is a special stand alone page for iframe embed of mwEmbed modules
 *
 * For now we just support the embedPlayer
 *
 * This enables sharing mwEmbed player without js includes ie:
 *
 * <iframe src="mwEmbedFrame.php?src={SRC URL}&poster={POSTER URL}&width={WIDTH}etc"> </iframe>
 */

// Setup the mwEmbedFrame
$myMwEmbedFrame = new mwEmbedFrame();

// Do mwEmbedFrame video output:
$myMwEmbedFrame->outputIFrame();

/**
 * mwEmbed iFrame class
 */
class mwEmbedFrame {
	/**
	 * Variables set by the Frame request:
	 */
	var $playerAttributes = array(
		'apiTitleKey',
		'apiProvider',
		'durationHint',
		'poster',
		'kentryid',
		'kwidgetid',
		'kuiconfid',
		'kplaylistid',
		'skin'			
	);
	var $playerIframeId = 'iframeVid';

	// When used in direct source mode the source asset.
	// NOTE: can be an array of sources in cases of "many" sources set
	var $sources = array();

	function __construct(){
		//parse input:
		$this->parseRequest();
	}
	
	// Parse the embedFrame request and sanitize input
	private function parseRequest(){	
		// Check for / attribute type request and update "REQUEST" global 
		// ( uses kaltura standard entry_id/{entryId} request )
		// normalize to the REQUEST object
		// @@FIXME: this should be moved over to a kaltura specific iframe implementation  
		if( $_SERVER['REQUEST_URI'] ){
			$kalturaUrlMap = Array( 
				'entry_id' => 'kentryid',
				'uiconf_id' => 'kuiconfid',
				'wid' => 'kwidgetid',
				'playlist_id' => 'kplaylistid'
			);
			$urlParts = explode( '/', $_SERVER['REQUEST_URI'] );
			foreach( $urlParts as $inx => $urlPart ){
				foreach( $kalturaUrlMap as $urlKey => $reqeustAttribute ){					
					if( $urlPart == $reqeustAttribute && isset( $urlParts[$inx+1] ) ){
						$_REQUEST[ $reqeustAttribute ] = $urlParts[$inx+1];
					}				
				}				
			}
		}		
		// Check for attributes
		foreach( $this->playerAttributes as $attributeKey){
			if( isset( $_REQUEST[ $attributeKey ] ) ){				
				$this->$attributeKey = htmlspecialchars( $_REQUEST[$attributeKey] );
			}
		}

		// Check for debug flag
		if( isset( $_REQUEST['debug'] ) ){
			$this->debug = true;
		}
		
		// Process the special "src" attribute
		if( isset( $_REQUEST['src'] ) ){
			if( is_array( $_REQUEST['src'] ) ){
				foreach($_REQUEST['src'] as $src ){
					$this->sources[] = htmlspecialchars( $src );
				}
			} else {
				$this->sources = array( htmlspecialchars( $_REQUEST['src'] ) );
			}
		}
	
	}
	private function getVideoTag( ){
		// Add default video tag with 100% width / height 
		// ( parent embed is responsible for setting the iframe size )
		$o = '<video id="' . htmlspecialchars( $this->playerIframeId ) . '" style="width:100%;height:100%"';
		foreach( $this->playerAttributes as $attributeKey){
			if( isset( $this->$attributeKey ) ){
				$o.= ' ' . $attributeKey . '="' . htmlspecialchars( $this->$attributeKey ) . '"';
			}
		}
		//Close the video attributes
		$o.='>';
		// Output each source
		if( count( $this->sources ) ){
			foreach($this->sources as $src ){
				$o.= '<source src="' . htmlspecialchars( $src ) . '"></source>';
			}
		}
		$o.= '</video>';		
		return $o;
	}   
	
	function outputIFrame( ){
		// Setup the embed string based on attribute set:
		$embedResourceList = 'window.jQuery,mwEmbed,mw.style.mwCommon,$j.fn.menu,mw.style.jquerymenu,mw.EmbedPlayer,mw.EmbedPlayerNative,mw.EmbedPlayerJava,mw.PlayerControlBuilder,$j.fn.hoverIntent,mw.style.EmbedPlayer,$j.cookie,$j.ui,mw.style.ui_redmond,$j.widget,$j.ui.mouse,mw.PlayerSkinKskin,mw.style.PlayerSkinKskin,mw.TimedText,mw.style.TimedText,$j.ui.slider';
		
		if( $this->kentryid ){
			 $embedResourceList.= ',' . implode(',', array(	
			 		'KalturaClientBase',
					'KalturaClient',
					'KalturaAccessControlService',
					'KalturaAccessControlOrderBy',
					'KalturaAccessControl',
					'MD5',
					'mw.KWidgetSupport',
					'mw.KAnalytics', 
					'mw.KDPMapping',
					'mw.MobileAdTimeline',		
					'mw.KAds'
			) );
		}
?><!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>mwEmbed iframe</title>
		<style type="text/css">
			body {				
				margin:0;					
				position:fixed;
				top:0px;
				left:0px;
				bottom:0px;
				right:0px;
				
			}
		</style>
    </head>
    <body>    
    <?
    // Check if we have a way to get sources:
    if( isset( $this->apiTitleKey ) || isset( $this->kentryid ) || count( $this->sources ) != 0 ) {
		echo $this->getVideoTag();
    } else {
    	echo "Error: mwEmbedFrame missing required parameter for video sources</body></html>";
    	exit(1);
    }    
    ?>
    
   		<script type="text/javascript" src="<?php echo str_replace( 'mwEmbedFrame.php', '', $_SERVER['SCRIPT_NAME'] ); ?>ResourceLoader.php?class=<?php 
			echo $embedResourceList;
			if( $this->debug ){
				echo '&debug=true';
			} 
		?>"></script>
		
		<script type="text/javascript">
			// Set some iframe embed config:
			// We can't support full screen in object context since it requires outter page DOM control
			mw.setConfig( 'EmbedPlayer.EnableFullscreen', false );

			// Enable the iframe player server:
			mw.setConfig( 'EmbedPlayer.EnableIFramePlayerServer', true );
			
			mw.ready(function(){						
				// Bind window resize to reize the player: 
				$j(window).resize(function(){											
					$j( '#<?php echo htmlspecialchars( $this->playerIframeId )?>' )
						.get(0).resizePlayer({
							'width' : $j(window).width(),
							'height' :  $j(window).height()
						}); 
				});
			});
		</script>
    </body>
</html>
<?php
	}
}
	?>
