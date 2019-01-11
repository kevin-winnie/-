<?php
class OLERead {
    var $data = '';
    function OLERead(){
    }
    
    function read($sFileName){
        
    	// check if file exist and is readable (Darko Miljanovic)
    	if(!is_readable($sFileName)) {
    		$this->error = 1;
    		return false;
    	}
    	
    	$this->data = @file_get_contents($sFileName);
    	if (!$this->data) { 
    		$this->error = 1; 
    		return false; 
   		}
   		//echo pack("CCCCCCCC",0xd0,0xcf,0x11,0xe0,0xa1,0xb1,0x1a,0xe1);
        //echo 'start';
        $cc = pack("CCCCCCCC", 0xd0, 0xcf, 0x11, 0xe0, 0xa1, 0xb1, 0x1a, 0xe1);
        if (substr($this->data, 0, 8) != $cc) {
            $this->error = 1; 
    		return false; 
   		}
        $this->numBigBlockDepotBlocks = $this->GetInt4d($this->data, 0x2c);
        $this->sbdStartBlock = $this->GetInt4d($this->data, 0x3c);
        $this->rootStartBlock = $this->GetInt4d($this->data, 0x30);
        $this->extensionBlock = $this->GetInt4d($this->data, 0x44);
        $this->numExtensionBlocks = $this->GetInt4d($this->data, 0x48);

        /*
        echo $this->numBigBlockDepotBlocks." ";
        echo $this->sbdStartBlock." ";
        echo $this->rootStartBlock." ";
        echo $this->extensionBlock." ";
        echo $this->numExtensionBlocks." ";
        */
        //echo "sbdStartBlock = $this->sbdStartBlock\n";
        $bigBlockDepotBlocks = array();
        $pos = 0x4c;
        // echo "pos = $pos";
	$bbdBlocks = $this->numBigBlockDepotBlocks;
        
            if ($this->numExtensionBlocks != 0) {
                $bbdBlocks = (0x200 - 0x4c) / 4;
        }
        
        for ($i = 0; $i < $bbdBlocks; $i++) {
              $bigBlockDepotBlocks[$i] = $this->GetInt4d($this->data, $pos);
            $pos += 4;
        }
        
        
        for ($j = 0; $j < $this->numExtensionBlocks; $j++) {
            $pos = ($this->extensionBlock + 1) * 0x200;
            $blocksToRead = min($this->numBigBlockDepotBlocks - $bbdBlocks, 0x200 / 4 - 1);

            for ($i = $bbdBlocks; $i < $bbdBlocks + $blocksToRead; $i++) {
                $bigBlockDepotBlocks[$i] = $this->GetInt4d($this->data, $pos);
                $pos += 4;
            }   

            $bbdBlocks += $blocksToRead;
            if ($bbdBlocks < $this->numBigBlockDepotBlocks) {
                $this->extensionBlock = $this->GetInt4d($this->data, $pos);
            }
        }

       // var_dump($bigBlockDepotBlocks);
        
        // readBigBlockDepot
        $pos = 0;
        $index = 0;
        $this->bigBlockChain = array();
        
        for ($i = 0; $i < $this->numBigBlockDepotBlocks; $i++) {
            $pos = ($bigBlockDepotBlocks[$i] + 1) * 0x200;
            //echo "pos = $pos";	
            for ($j = 0; $j < 0x200 / 4; $j++) {
                $this->bigBlockChain[$index] = $this->GetInt4d($this->data, $pos);
                $pos += 4 ;
                $index++;
            }
        }

	//var_dump($this->bigBlockChain);
        //echo '=====2';
        // readSmallBlockDepot();
        $pos = 0;
	    $index = 0;
	    $sbdBlock = $this->sbdStartBlock;
	    $this->smallBlockChain = array();
	
	    while ($sbdBlock != -2) {
	
	      $pos = ($sbdBlock + 1) * 0x200;

            for ($j = 0; $j < 0x200 / 4; $j++) {
                $this->smallBlockChain[$index] = $this->GetInt4d($this->data, $pos);
                $pos += 4;
	        $index++;
	      }
	
	      $sbdBlock = $this->bigBlockChain[$sbdBlock];
	    }

        
        // readData(rootStartBlock)
        $block = $this->rootStartBlock;
        $pos = 0;
        $this->entry = $this->__readData($block);
        
        /*
        while ($block != -2)  {
            $pos = ($block + 1) * 0x200;
            $this->entry = $this->entry.substr($this->data, $pos, 0x200);
            $block = $this->bigBlockChain[$block];
        }
        */
        //echo '==='.$this->entry."===";
        $this->__readPropertySets();

    }
    
     function __readData($bl) {
        $block = $bl;
        $pos = 0;
        $data = '';
        
        while ($block != -2)  {
            $pos = ($block + 1) * 0x200;
            $data = $data . substr($this->data, $pos, 0x200);
            //echo "pos = $pos data=$data\n";	
	    $block = $this->bigBlockChain[$block];
        }
		return $data;
     }
        
    function __readPropertySets(){
        $offset = 0;
        //var_dump($this->entry);
        while ($offset < strlen($this->entry)) {
              $d = substr($this->entry, $offset, 0x80);

            $nameSize = ord($d[0x40]) | (ord($d[0x40 + 1]) << 8);

            $type = ord($d[0x42]);
            //$maxBlock = strlen($d) / 0x200 - 1;

            $startBlock = $this->GetInt4d($d, 0x74);
            $size = $this->GetInt4d($d, 0x78);

            $name = '';
            for ($i = 0; $i < $nameSize ; $i++) {
              $name .= $d[$i];
            }
            
            $name = str_replace("\x00", "", $name);
            
            $this->props[] = array (
                'name' => $name, 
                'type' => $type,
                'startBlock' => $startBlock,
                'size' => $size);

            if (($name == "Workbook") || ($name == "Book")) {
                $this->wrkbook = count($this->props) - 1;
            }

            if ($name == "Root Entry") {
                $this->rootentry = count($this->props) - 1;
            }
            
            //echo "name ==$name=\n";

            
            $offset += 0x80;
        }   
        
    }
    
    
    function getWorkBook(){
    	if ($this->props[$this->wrkbook]['size'] < 0x1000) {
//    	  getSmallBlockStream(PropertyStorage ps)

			$rootdata = $this->__readData($this->props[$this->rootentry]['startBlock']);
	        
			$streamData = '';
	        $block = $this->props[$this->wrkbook]['startBlock'];
	        //$count = 0;
	        $pos = 0;
		    while ($block != -2) {
      	          $pos = $block * 0x40;
                $streamData .= substr($rootdata, $pos, 0x40);

                $block = $this->smallBlockChain[$block];
		    }
			
		    return $streamData;
    		

    	}else{
    	
	        $numBlocks = $this->props[$this->wrkbook]['size'] / 0x200;
            if ($this->props[$this->wrkbook]['size'] % 0x200 != 0) {
                $numBlocks++;
	        }
	        
	        if ($numBlocks == 0) return '';
	        
	        //echo "numBlocks = $numBlocks\n";
	    //byte[] streamData = new byte[numBlocks * 0x200];
            //print_r($this->wrkbook);
	        $streamData = '';
	        $block = $this->props[$this->wrkbook]['startBlock'];
	        //$count = 0;
	        $pos = 0;
	        //echo "block = $block";
	        while ($block != -2) {
	          $pos = ($block + 1) * 0x200;
                $streamData .= substr($this->data, $pos, 0x200);
                $block = $this->bigBlockChain[$block];
	        }   
	        //echo 'stream'.$streamData;
	        return $streamData;
    	}
    }

    function GetInt4d($data, $pos) {
        $value = ord($data[$pos]) | (ord($data[$pos + 1]) << 8) | (ord($data[$pos + 2]) << 16) | (ord($data[$pos + 3]) << 24);
        if ($value >= 4294967294) {
            $value = -2;
        }
        return $value;
    }

}
?>