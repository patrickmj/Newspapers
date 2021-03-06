<?php

class NewspapersFrontPage extends Omeka_Record_AbstractRecord
{
    
    public $date;
    
    public $item_id;
    
    public $issue_id;
    
    public $loc_uri;
    
    public $page_height;
    
    public $page_width;
    
    public $printspace_height;
    
    public $printspace_width;
    
    public $printspace_vpos;
    
    public $printspace_hpos;
    
    public $columns;
    
    public $columns_confidence;
    
    public $pdf_url;
    
    public function getItem()
    {
         return $this->_db->getTable('Item')->find($this->item_id);
    }
    
    public function dimensionsSvg()
    {
        
        $scale = 150; //first scale-down for display
        $baseHeight = NEWSPAPERS_MAX_HEIGHT / $scale;
        $baseWidth = NEWSPAPERS_MAX_WIDTH / $scale;
        
        //when I know the real max and min widths from all papers, use
        //that to normalize to the base width and height
        
        $normalizedWidth = $this->page_width / $scale;
        $normalizedHeight = $this->page_height / $scale;
        
        $colElWidth = $normalizedWidth - 2;
        $colElHeight = $normalizedHeight - 2;
        
        if ($this->columns == 0) {
            $colWidthScale = .90;
        } else {
            $colWidthScale = .90 / $this->columns; //fudge the width to be a smidge smaller than 1/cols
        }
        
        $avgWidth  = NEWSPAPERS_AVG_WIDTH / $scale; // temp @todo
        $avgHeight = NEWSPAPERS_AVG_HEIGHT / $scale; // temp @todo
        
        
        $svg = "
        
<svg xmlns='http://www.w3.org/2000/svg' width='$baseWidth' height='$baseHeight' fill='white'>
    <g id='column' transform='translate(40, 10)'>
        <rect width='$normalizedWidth' height='$normalizedHeight' fill='white'></rect>
        <rect x='1' y='1'  width='$colElWidth' height='$colElHeight' fill='gray'></rect>
    </g>
    
    
    <rect id='background' width='$baseWidth' height='$baseHeight' color='white' fill='white' stroke='black'></rect>
    <rect id='average-dimensions' width='$avgWidth' height='$avgHeight' stroke='grey' stroke-linecap='miter' stroke-dasharray='2' stroke-width='5'></rect>
    <rect id='front-page' x='5' y='5' width='$normalizedWidth' height='$normalizedHeight' stroke='black'></rect>
    <text x='$avgWidth' y='$avgHeight' transform='rotate(45, $avgWidth, $avgHeight)' stroke='grey'>&#9754; Average</text>
    
    
    ";
        if ($this->columns == 1) {
            $svg .= "
                <use xlink:href='#column' x='2px' transform='translate(-30) scale($colWidthScale, .9)'></use>
            ";
        } else {
            for($col = 0; $col < $this->columns; $col++) {
                $x = ($normalizedWidth * $col);
                $svg .= "
                <use xlink:href='#column' x='$x' transform='scale($colWidthScale, .9)'></use>
                ";
            }
        }

        
        
        
        $svg .= "</svg>";
        
        return $svg;
    }
    
    public function getHeight()
    {
        $inches = round($this->page_height / 1200, 2);
        return $inches . '"';
    }
    
    public function getWidth()
    {
        $inches = round($this->page_width / 1200, 2);
        return $inches . '"';
    }
}
