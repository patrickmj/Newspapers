<?php

class AltoDoc
{
    public $alto;
    
    public $xpath;
    
    public $tbData = array();
    
    public $tlData = array();
    
    public $pageLayout = array(); //height and width of page, and of printspace
    
    public $columnCount;
    
    protected $textLines;
    
    protected $textBlocks;
    
    public function __construct($alto)
    {
        $this->alto = new DOMDocument();
        $this->alto->load($alto);
        
        $this->xpath = new DOMXPath($this->alto);
        //srsly? the namespace for these documents isn't consistent?
        $altoNSUri = $this->alto->documentElement->lookupNamespaceUri(null);
        $this->xpath->registerNamespace('alto', $altoNSUri);
        $this->setPageLayout();
    }
    
    protected function setPageLayout()
    {
        $pageHeight = $this->xpath->query("//alto:Page/@HEIGHT")->item(0)->value;
        $pageWidth = $this->xpath->query('//alto:Page/@WIDTH')->item(0)->value;
        $printSpaceWidth = $this->xpath->query('//alto:PrintSpace/@WIDTH')->item(0)->value;
        $printSpaceHeight = $this->xpath->query('//alto:PrintSpace/@HEIGHT')->item(0)->value;
        $printSpaceHpos = $this->xpath->query('//alto:PrintSpace/@HPOS')->item(0)->value;
        $printSpaceVpos = $this->xpath->query('//alto:PrintSpace/@VPOS')->item(0)->value;
        $pageLayout = array(
                'page'         => array('height' => $pageHeight, 'width' => $pageWidth),
                'printSpace'   => array(
                        'height' => $printSpaceHeight,
                        'width'  => $printSpaceWidth,
                        'hpos'   => $printSpaceHpos,
                        'vpos'   => $printSpaceVpos
                        )
                );
        $this->pageLayout = $pageLayout;
    }
    
    /**
     * Works with either array or nodelist
     * @param array or DOMNodeList $tlNodes
     */
    public function getTbsForTls($tlNodes) 
    {
        $tbNodes = array();
        foreach ($tlNodes as $tlNode) {
            $tbNodes[] = $tlNode->parentNode;
        }
        return $tbNodes;
    }
    
    
    // @TODO
    //abstract this to filterNodesByAttributeSd($nodes, $attributeName, $sdFudge = 1.1, $sdSkew = null)
    
    
    public function filterTlsByWidthSd($tlNodes, $tlWidthsSdFudge = 1.1, $tlWidthsSdSkew = null)
    {
        if (is_null($tlNodes)) {
            $tlNodes = $this->getTextLines();
        }
        
        if (is_object($tlNodes)) {
            $tlNodes = $this->nodeListToArray($tlNodes);
        }
        
        $tlWidths = array();
        foreach($tlNodes as $tlNode) {
            $tlWidths[] = $tlNode->getAttribute('WIDTH');
        }
        $tlWidthsInSd = $this->findInStandardDeviation($tlWidths, $tlWidthsSdFudge, $tlWidthsSdSkew);

        $tlWidthsMax = max($tlWidthsInSd);
        $tlWidthsMin = min($tlWidthsInSd);
        $filteredTlNodes = array();
        foreach($tlNodes as $tlNode) {
                $width = $tlNode->getAttribute('WIDTH');
                if ($width > $tlWidthsMin && $width < $tlWidthsMax) {
                    $filteredTlNodes[] = $tlNode;
                }
                
        }
        
        return $filteredTlNodes;
    }
    
    public function filterTbsByWidthSd($tbNodes, $tbWidthsSdFudge = 1.1, $tbWidthsSdSkew = null)
    {
        
        $tbWidths = array();
        foreach($tbNodes as $tbNode) {
            $tbWidths[] = $tbNode->getAttribute('WIDTH');
        }
        $filteredTbNodes = $this->findInStandardDeviation($tbWidths, $tbWidthsFudge, $tbWidthsSkew);
        return $filteredTbNodes;
        
    }
    
    public function filterTlsByHeightSd($tlNodes = null, $tlHeightsSdFudge = 1.1, $tlHeightsSdSkew = null)
    {
        if (is_null($tlNodes)) {
            $tlNodes = $this->getTextLines();
        }
        
        if (is_object($tlNodes)) {
            $tlNodes = $this->nodeListToArray($tlNodes);
        }
        
        $tlHeights = $this->findTlHeights($tlNodes);
        $tlHeightsInSd = $this->findInStandardDeviation($tlHeights, $tlHeightsSdFudge, $tlHeightsSdSkew);

        $tlHeightsMax = max($tlHeightsInSd);
        $tlHeightsMin = min($tlHeightsInSd);
        $filteredTlNodes = array();
        foreach($tlNodes as $tlNode) {
                $height = $tlNode->getAttribute('HEIGHT');
                if ($height > $tlHeightsMin && $height < $tlHeightsMax) {
                    $filteredTlNodes[] = $tlNode;
                }
                
        }
        
        return $filteredTlNodes;
    }
    
    /**
     * 
     * @param array $tlNodes
     * @param $vposPercent percent from top of print space 1 is the bottom of the page
     */
    
    public function filterTlsByVpos($tlNodes = null, $vposPercent = .5)
    {
        if (is_null($tlNodes)) {
            $tlNodes = $this->getTextLines();
        }
        if (is_object($tlNodes)) {
            $tlNodes = $this->nodeListToArray($tlNodes);
        }
        
        $printSpaceTop = (int) $this->pageLayout['printSpace']['vpos'];
        $printSpaceHeight = $this->pageLayout['printSpace']['height'];
        
        $pageHeight = $this->pageLayout['page']['height'];
        $filterTop = (int) $pageHeight * $vposPercent;
        $filteredTlNodes = array();
        foreach($tlNodes as $id => $tlNode) {
            $tlVpos = $tlNode->getAttribute('VPOS');
            if ($tlVpos > $filterTop) {
                $filteredTlNodes[$id] = $tlNode;
            }
        }
        return $filteredTlNodes;
    }
    
    public function guessColumnsFromTbs($tbNodes, 
                                        $tbWSdTweak = 1,
                                        $tbWSdSkew = null,
                                        $widthsTweak = 1.0175,
                                        $bumpDownThreshold = .1
            )
    {
        $tbWidths = array();
        foreach($tbNodes as $tbNode) {
            $tbWidths[] = $tbNode->getAttribute('WIDTH');
        }
        $likelyWidthsByTbs = $this->findInStandardDeviation($tbWidths, $tbWSdTweak, $tbWSdSkew);
        
        $maxLikelyWidthByTbs = max($likelyWidthsByTbs);
        $minLikelyWidthByTbs = min($likelyWidthsByTbs);
        
        //more fudging!
        $maxLikelyWidthByTbs = $maxLikelyWidthByTbs * $widthsTweak;
        
        //maybe, if less than .1 above floor value, bump it an integer down?
        //yet another vector of tweaking results in hopes of getting closer to reality
        //this doesn't please me.
        
        //compare sum of tbWidths to page width and/or printSpace width
        $rawColumnsGuess = $this->pageLayout['page']['width'] / $maxLikelyWidthByTbs;
        $rawColumnsGuess = $this->pageLayout['printSpace']['width'] / $maxLikelyWidthByTbs;
        $floorColumnsGuess = floor($rawColumnsGuess);
        if( ($rawColumnsGuess - $floorColumnsGuess) < $bumpDownThreshold) {
            $columnsByTbs = $floorColumnsGuess - 1;
        } else {
            $columnsByTbs = $floorColumnsGuess;
        }
        
        //end maybe
        
        //or, compare sum of tb widths to the printSpace width.
        //or, compare to page width
        //and do something
        
        
        return $columnsByTbs;
    }
    
    
    public function guessColumnsFromTls($tlNodes,
                                       $tlWSdTweak = .7,
                                       $tlWSdSkew = null,
                                       $widthsTweak = 1.12,
                                       $bumpDownThreshold = .1
            )
    {
        $tlWidths = array();
        foreach($tlNodes as $tlNode) {
            $tlWidths[] = $tlNode->getAttribute('WIDTH');
        }
      //  pre_print_r($tbWidths);
        
        $likelyWidthsByTls = $this->findInStandardDeviation($tlWidths, $tlWSdTweak, $tlWSdSkew);

        $maxLikelyWidthByTls = max($likelyWidthsByTls);
        $minLikelyWidthByTls = min($likelyWidthsByTls);

        $maxLikelyWidthByTls = $maxLikelyWidthByTls * $widthsTweak;
        $rawColumnsGuess = $this->pageLayout['printSpace']['width'] / $maxLikelyWidthByTls;
        $columnsByTls = floor($this->pageLayout['page']['width'] / $maxLikelyWidthByTls);
        
        //is there sometimes a difference btw page and printSpace dimensions?
        return floor($this->pageLayout['page']['width'] / $maxLikelyWidthByTls);
        //return floor($this->pageLayout['page']['width'] / $minLikelyWidthByTls);
        //return ceil($this->pageLayout['printSpace']['width'] / $maxLikelyWidthByTls);
        //return floor($this->pageLayout['printSpace']['width'] / $maxLikelyWidthByTbs);
        
    }

    
    public function findTlHeights($tlNodes)
    {
        if (is_null($tlNodes)) {
            $tlNodes = $this->getTextLines();
        }
        
        if (is_object($tlNodes)) {
            $tlNodes = $this->nodeListToArray($tlNodes);
        }
        $tlHeights = array();

        foreach($tlNodes as $tlNode) {
            $height = $tlNode->getAttribute('HEIGHT');
            $tlHeights[] = $height;
        }

        return $tlHeights;
    }
    
    /*
     * Get the data for how many TLs are in each TB
     */
    public function countTlsByTbs()
    {
        $tlsByTbs = array();
        $tbNodes = $this->getTextBlocks();

        foreach($tbNodes as $tbNode) {
            $tbId = $tbNode->getAttribute('ID');
            $tlNodes = $this->getTextLines();
            $tlsByTbs[$tbId] = $tlNodes->length;
        }
        return $tlsByTbs;
    }
    
    public function findTbWidths($tbNodes)
    {
        $tbWidths = array();
        foreach($tbIds as $tbId => $tlCount) {
            $xpath = "//alto:TextBlock[@ID = '$tbId']";
            $tb = $this->xpath->query($xpath)->item(0);
            $width = $tb->getAttribute('WIDTH');
            $tbWidths[$tbId] = $width;
        }
        return $tbWidths;
    }
    
    public function getTextLines($asArray = true)
    {
        if (! $this->textLines) {
            $this->textLines = $this->xpath->query('//alto:TextLine');
        }
        
        if ($asArray) {
            return $this->nodeListToArray($this->textLines);
        }
        return $this->textLines;
    }
    
    public function getTextBlocks($asArray = true)
    {
        if (! $this->textBlocks) {
            $this->textBlocks = $this->xpath->query('//alto:TextBlocks');
        }
        
        if ($asArray) {
            return $this->nodeListToArray($this->textBlocks);
        }
        return $this->textBlocks;
    }
    
    public function findStandardDeviation(array $a, $sample = false)
    {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
           --$n;
        }
        return sqrt($carry / $n);
    }
    
    
    /**
     * Return array key = node ID, value is the value passed in with $data
     * @param unknown_type $data
     * @param unknown_type $tweak
     * @param unknown_type $skew
     */
    
    public function findInStandardDeviation($data, $tweak = 1, $skew = null)
    {
        $sd = $this->findStandardDeviation($data);
        $mean = array_sum($data) / count($data);
        $sd = $sd * $tweak;
        $top = $mean + $sd;
        $bottom = $mean - $sd;
        $in = array();
        foreach ($data as $value) {
            switch($skew) {
                case 'low':
                    if ( ($value < $mean ) && ($value > $bottom ) ) {
                        $in[] = $value;
                    }
                break;
                case 'high':
                    if ( ($value > $mean ) && ($value < $top ) ) {
                        $in[] = $value;
                    }
                break;
                
                default:
                    if ( ($value < $top ) && ($value > $bottom ) ) {
                        $in[] = $value;
                    }
            }

        }
        return $in;
    }
    

    
    protected function nodeListToArray($nodeList)
    {
        $nodesArray = array();
        foreach($nodeList as $node) {
            $nodesArray[] = $node;
        }
        return $nodesArray;
    }
    
}
