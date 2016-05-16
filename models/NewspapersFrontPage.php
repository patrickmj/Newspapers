<?php

class NewspapersFrontPage extends Omeka_Record_AbstractRecord
{
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
    
    
    public function dimensionsSvg()
    {
        $maxHeight = 53000; //based on max in db
        $maxWidth = 44000; //based on max in db
        $avgWidth = 21434;
        $avgHeight = 28706;
        
        
        $scale = 150; //first scale-down for display
        $baseHeight = $maxHeight / $scale;
        $baseWidth = $maxWidth / $scale;
        
        //when I know the real max and min widths from all papers, use
        //that to normalize to the base width and height
        
        $normalizedWidth = $this->page_width / $scale;
        $normalizedHeight = $this->page_height / $scale;
        
        $colElWidth = $normalizedWidth - 2;
        $colElHeight = $normalizedHeight - 2;
        
        $colWidthScale = .90 / $this->columns; //fudge the width to be a smidge smaller than 1/cols
        
        $avgWidth  = $avgWidth / $scale; // temp @todo
        $avgHeight = $avgHeight / $scale; // temp @todo
        
        
        $svg = "
        
<svg xmlns='http://www.w3.org/2000/svg' width='$baseWidth' height='$baseHeight' fill='white'>
    <g id='column' transform='translate(40, 10)'>
        <rect width='$normalizedWidth' height='$normalizedHeight' fill='white'></rect>
        <rect x='1' y='1'  width='$colElWidth' height='$colElHeight' fill='gray'></rect>
    </g>
    
    
    <rect id='background' width='$baseWidth' height='$baseHeight' color='white' fill='white' stroke='black'></rect>
    <rect id='average-dimensions' width='$avgWidth' height='$avgHeight' stroke='red' stroke-width='5'></rect>
    <rect id='front-page' x='5' y='5' width='$normalizedWidth' height='$normalizedHeight' stroke='black'></rect>
    
    
    
    ";
        
        for($col = 0; $col < $this->columns; $col++) {
            $x = $normalizedWidth * $col;
            $svg .= "
            <use xlink:href='#column' x='$x' transform='scale($colWidthScale, .95)'></use>
            ";
        }
        
        
        
        $svg .= "</svg>";
        
        return $svg;
    }
}
