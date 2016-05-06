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
        $baseWidth = 240;
        $baseHeight = 240;
        //when I know the real max and min widths from all papers, use
        //that to normalize to the base width and height
        
        $normalizedWidth = 200; //fake data!
        $normalizedHeight = 230; //fake data!
        
        $colElWidth = $normalizedWidth - 20;
        $colElHeight = $normalizedHeight - 10;
        
        $colWidthScale = .99/$this->columns; //fudge the width to be a smidge smaller than 1/cols
        
        $svg = "
        
<svg xmlns='http://www.w3.org/2000/svg' width='$baseWidth' height='$baseHeight' fill='white'>
    <g id='column' transform='translate(40, 10)'>
        <rect width='$normalizedWidth' height='$normalizedHeight' fill='white'></rect>
        <rect x='4' y='4'  width='$colElWidth' height='$colElHeight' fill='gray'></rect>
    </g>
    
    <rect width='$baseWidth' height='$baseHeight' color='white' fill='white' stroke='black'></rect>
    <rect x='5' y='5' width='$normalizedWidth' height='$normalizedHeight' stroke='black'></rect>
    
    
    
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
