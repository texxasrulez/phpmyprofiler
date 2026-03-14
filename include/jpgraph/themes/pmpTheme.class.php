<?php

/**
* pmpTheme class
*/
class pmpTheme extends Theme 
{
    private $font_color = '#1f3b5f';
    private $background_color = '#e9f2fc';
    private $back_inside_color = '#e9f2fc';
    private $axis_color = '#a7bbd4';
    private $grid_color = '#b8c9dd';
    private $pie_value_color = '#1f3b5f';
    private $bar_fill_from = array(72, 125, 189);
    private $bar_fill_to = array(163, 201, 240);
    private $plot_colors = array();
    private $transparent_background = true;

    function __construct() {
        parent::__construct();
        $this->applyThemePalette();
    }

    private function applyThemePalette() {
        global $pmp_theme, $pmp_theme_css;

        $theme = isset($pmp_theme) ? strtolower(trim((string) $pmp_theme)) : 'default';
        $variant = isset($pmp_theme_css) ? strtolower((string) $pmp_theme_css) : 'default.css';

        if ($theme === 'sunrise') {
            $this->font_color = '#5e3a2d';
            $this->background_color = '#fdeee8';
            $this->back_inside_color = '#fdeee8';
            $this->axis_color = '#ddb9aa';
            $this->grid_color = '#e8cec1';
            $this->pie_value_color = '#563327';
            $this->bar_fill_from = array(225, 132, 102);
            $this->bar_fill_to = array(250, 199, 181);
            $this->plot_colors = array(
                '#e18466',
                '#f1a286',
                '#c46a57',
                '#f4beaa',
                '#a85a4b',
                '#f7d5c7',
                '#8a4b3f',
                '#f9e3da',
            );

            if (strpos($variant, 'red') !== false) {
                $this->bar_fill_from = array(191, 87, 72);
                $this->bar_fill_to = array(236, 152, 138);
            } elseif (strpos($variant, 'black') !== false) {
                $this->bar_fill_from = array(134, 104, 95);
                $this->bar_fill_to = array(218, 177, 163);
            }
        } elseif ($theme === 'slate') {
            $this->font_color = '#1e3654';
            $this->background_color = '#e9f2fc';
            $this->back_inside_color = '#e9f2fc';
            $this->axis_color = '#a6bdd8';
            $this->grid_color = '#c0d2e6';
            $this->pie_value_color = '#1b3351';
            $this->bar_fill_from = array(76, 137, 201);
            $this->bar_fill_to = array(168, 204, 238);
            $this->plot_colors = array(
                '#4c89c9',
                '#6da4db',
                '#2f6cae',
                '#89b7e5',
                '#5b7ea8',
                '#a6c8ed',
                '#1f4f83',
                '#bfd9f4',
            );

            if (strpos($variant, 'red') !== false) {
                $this->bar_fill_from = array(179, 88, 92);
                $this->bar_fill_to = array(226, 153, 157);
            } elseif (strpos($variant, 'black') !== false) {
                $this->bar_fill_from = array(92, 112, 140);
                $this->bar_fill_to = array(166, 188, 214);
            }
        } else {
            $this->plot_colors = array(
                '#4d7cb5',
                '#6e99cb',
                '#2f5f99',
                '#8db3df',
                '#5b6f8d',
                '#b1c9e9',
                '#234978',
                '#c8d9ef',
            );

            if (strpos($variant, 'red') !== false) {
                $this->bar_fill_from = array(176, 80, 80);
                $this->bar_fill_to = array(224, 148, 148);
            } elseif (strpos($variant, 'black') !== false) {
                $this->bar_fill_from = array(88, 103, 128);
                $this->bar_fill_to = array(156, 176, 205);
            }
        }
    }

    function GetColorList() {
        if (!is_array($this->plot_colors) || count($this->plot_colors) === 0) {
            return array(
                '#4d7cb5',
                '#6e99cb',
                '#2f5f99',
                '#8db3df',
                '#5b6f8d',
                '#b1c9e9',
                '#234978',
                '#c8d9ef',
            );
        }
        return $this->plot_colors;
    }

    function GetAxisLineColor() {
        return $this->axis_color;
    }

    function GetAxisTextColor() {
        return $this->font_color;
    }

    function GetGridColor() {
        return $this->grid_color;
    }

    function GetPieValueColor() {
        return $this->pie_value_color;
    }

    function GetBarGradient() {
        return array($this->bar_fill_from, $this->bar_fill_to);
    }

    function IsTransparentBackground() {
        return $this->transparent_background;
    }

    function SetupGraph($graph) {

        // graph
        /*
        $img = $graph->img;
        $height = $img->height;
        $graph->SetMargin($img->left_margin, $img->right_margin, $img->top_margin, $height * 0.25);
        */
        if ($this->transparent_background) {
            $graph->SetFrame(false, 'white');
            $graph->SetMarginColor('white');
            $graph->SetBackgroundGradient('white', 'white', GRAD_HOR, BGRAD_PLOT);
        } else {
            $graph->SetFrame(true, $this->background_color);
            $graph->SetMarginColor($this->background_color);
            $graph->SetBackgroundGradient($this->back_inside_color, $this->back_inside_color, GRAD_HOR, BGRAD_PLOT);
        }

        // legend
        $graph->legend->SetFrameWeight(0);
        $graph->legend->Pos(0.5, 0.85, 'center', 'top');
        $graph->legend->SetFillColor($this->back_inside_color);
        $graph->legend->SetLayout(LEGEND_HOR);
        $graph->legend->SetColumns(3);
        $graph->legend->SetShadow(false);
        $graph->legend->SetMarkAbsSize(5);

        // xaxis
        $graph->xaxis->title->SetColor($this->font_color);  
        $graph->xaxis->SetColor($this->axis_color, $this->font_color);    
        $graph->xaxis->SetTickSide(SIDE_BOTTOM);
        $graph->xaxis->SetLabelMargin(10);
                
        // yaxis
        $graph->yaxis->title->SetColor($this->font_color);  
        $graph->yaxis->SetColor($this->axis_color, $this->font_color);    
        $graph->yaxis->SetTickSide(SIDE_LEFT);
        $graph->yaxis->SetLabelMargin(8);
        $graph->yaxis->HideLine();
        $graph->yaxis->HideTicks();
        $graph->xaxis->SetTitleMargin(15);

        // font
        $graph->title->SetColor($this->font_color);
        $graph->subtitle->SetColor($this->font_color);
        $graph->subsubtitle->SetColor($this->font_color);

//        $graph->img->SetAntiAliasing();
    }


    function SetupPieGraph($graph) {

        // graph
        if ($this->transparent_background) {
            $graph->SetFrame(false, 'white');
            $graph->SetMarginColor('white');
            $graph->SetBackgroundGradient('white', 'white', GRAD_HOR, BGRAD_PLOT);
        } else {
            $graph->SetFrame(true, $this->background_color);
            $graph->SetMarginColor($this->background_color);
            $graph->SetBackgroundGradient($this->back_inside_color, $this->back_inside_color, GRAD_HOR, BGRAD_PLOT);
        }


        // legend
        $graph->legend->SetFillColor($this->back_inside_color);
        $graph->legend->SetFrameWeight(0);
        $graph->legend->Pos(0.5, 0.80, 'center', 'top');
        $graph->legend->SetLayout(LEGEND_HOR);
        $graph->legend->SetColumns(4);

        $graph->legend->SetShadow(false);
        $graph->legend->SetMarkAbsSize(5);

        // title
        $graph->title->SetColor($this->font_color);
        $graph->subtitle->SetColor($this->font_color);
        $graph->subsubtitle->SetColor($this->font_color);

        $graph->SetAntiAliasing();
    }


    function PreStrokeApply($graph) {
        if ($graph->legend->HasItems()) {
            $img = $graph->img;
            $height = $img->height;
            $graph->SetMargin($img->left_margin, $img->right_margin, $img->top_margin, $height * 0.25);
        }
    }

    function ApplyPlot($plot) {

        switch (get_class($plot))
        { 
            case 'GroupBarPlot':
            {
                foreach ($plot->plots as $_plot) {
                    $this->ApplyPlot($_plot);
                }
                break;
            }

            case 'AccBarPlot':
            {
                foreach ($plot->plots as $_plot) {
                    $this->ApplyPlot($_plot);
                }
                break;
            }

            case 'BarPlot':
            {
                $plot->Clear();

                $color = $this->GetNextColor();
                $plot->SetColor($color);
                $plot->SetFillColor($color);
                $plot->SetShadow('red', 3, 4, false);
                break;
            }

            case 'LinePlot':
            {
                $plot->Clear();
                $plot->SetColor($this->GetNextColor().'@0.4');
                $plot->SetWeight(2);
//                $plot->SetBarCenter();
                break;
            }

            case 'PiePlot':
            {
                $plot->SetCenter(0.5, 0.45);
                $plot->ShowBorder(false);
                $plot->SetSliceColors($this->GetThemeColors());
                break;
            }

            case 'PiePlot3D':
            {
                $plot->SetSliceColors($this->GetThemeColors());
                break;
            }
    
            default:
            {
            }
        }
    }
}


?>
