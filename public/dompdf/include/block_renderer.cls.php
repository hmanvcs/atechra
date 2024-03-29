<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: block_renderer.cls.php,v $
 * Created on: 2004-06-03
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf

 */

/* $Id: block_renderer.cls.php,v 1.1 2011/11/15 14:59:55 jmusoke Exp $ */

/**
 * Renders block frames
 *
 * @access private
 * @package dompdf
 */
class Block_Renderer extends Abstract_Renderer {

  //........................................................................

  function render(Frame $frame) {
    $style = $frame->get_style(); 
    list($x, $y, $w, $h) = $frame->get_padding_box();
    
    $this->_set_opacity( $frame->get_opacity( $style->opacity ) );

    // Draw our background, border and content
    if ( ($bg = $style->background_color) !== "transparent" ) {
      $this->_canvas->filled_rectangle( $x, $y, $w, $h, $bg );
    }

    if ( ($url = $style->background_image) && $url !== "none" )
      $this->_background_image($url, $x, $y, $w, $h, $style);

    $this->_render_border($frame);
    $this->_render_outline($frame);
    
    if (DEBUG_LAYOUT && DEBUG_LAYOUT_BLOCKS) {
      $this->_debug_layout($frame->get_border_box(), "red");
      if (DEBUG_LAYOUT_PADDINGBOX) {
        $this->_debug_layout($frame->get_padding_box(), "red", array(0.5, 0.5));
      }
    }
    
    if (DEBUG_LAYOUT && DEBUG_LAYOUT_LINES && $frame->get_decorator()) {
      foreach ($frame->get_decorator()->get_lines() as $line) {
        $frame->_debug_layout(array($line["x"], $line["y"], $line["w"], $line["h"]), "orange");
      }
    }
  }

  protected function _render_border(Frame_Decorator $frame, $corner_style = "bevel") {
    $style = $frame->get_style();
    $bbox = $frame->get_border_box();
    $bp = $style->get_border_properties();

    // If all the borders are "solid" with the same color and style, we'd better draw a rectangle
    if (
      in_array($bp["top"]["style"], array("solid", "dashed", "dotted")) && 
      $bp["top"]    == $bp["right"] &&
      $bp["right"]  == $bp["bottom"] &&
      $bp["bottom"] == $bp["left"]
    ) {
      $props = $bp["top"];
      if ( $props["color"] === "transparent" || $props["width"] <= 0 ) return;
      
      list($x, $y, $w, $h) = $bbox;
      $width = $style->length_in_pt($props["width"]);
      $pattern = $this->_get_dash_pattern($props["style"], $width);
      $this->_canvas->rectangle($x + $width / 2, $y + $width / 2, $w - $width, $h - $width, $props["color"], $width, $pattern);
      return;
    }

    $widths = array($style->length_in_pt($bp["top"]["width"]),
                    $style->length_in_pt($bp["right"]["width"]),
                    $style->length_in_pt($bp["bottom"]["width"]),
                    $style->length_in_pt($bp["left"]["width"]));
    
    foreach ($bp as $side => $props) {
      list($x, $y, $w, $h) = $bbox;

      if ( !$props["style"] || 
            $props["style"] === "none" || 
            $props["width"] <= 0 || 
            $props["color"] == "transparent" )
        continue;

      switch($side) {
      case "top":
        $length = $w;
        break;

      case "bottom":
        $length = $w;
        $y += $h;
        break;

      case "left":
        $length = $h;
        break;

      case "right":
        $length = $h;
        $x += $w;
        break;
      default:
        break;
      }
      $method = "_border_" . $props["style"];

      $this->$method($x, $y, $length, $props["color"], $widths, $side, $corner_style);
    }
  }

  protected function _render_outline(Frame_Decorator $frame, $corner_style = "bevel") {
    $style = $frame->get_style();
    
    $props = array(
      "width" => $style->outline_width,
      "style" => $style->outline_style,
      "color" => $style->outline_color,
    );
    
    if ( !$props["style"] || $props["style"] === "none" || $props["width"] <= 0 )
      return;
      
    $bbox = $frame->get_border_box();
    $offset = $style->length_in_pt($props["width"]);
    $pattern = $this->_get_dash_pattern($props["style"], $offset);

    // If the outline style is "solid" we'd better draw a rectangle
    if ( in_array($props["style"], array("solid", "dashed", "dotted")) ) {
      $bbox[0] -= $offset / 2;
      $bbox[1] -= $offset / 2;
      $bbox[2] += $offset;
      $bbox[3] += $offset;
    
      list($x, $y, $w, $h) = $bbox;
      $this->_canvas->rectangle($x, $y, $w, $h, $props["color"], $offset, $pattern);
      return;
    }

    $bbox[0] -= $offset;
    $bbox[1] -= $offset;
    $bbox[2] += $offset * 2;
    $bbox[3] += $offset * 2;
    
    $method = "_border_" . $props["style"];
    $widths = array_fill(0, 4, $props["width"]);
    $sides = array("top", "right", "left", "bottom");
    
    foreach ($sides as $side) {
      list($x, $y, $w, $h) = $bbox;

      switch($side) {
      case "top":
        $length = $w;
        break;

      case "bottom":
        $length = $w;
        $y += $h;
        break;

      case "left":
        $length = $h;
        break;

      case "right":
        $length = $h;
        $x += $w;
        break;
      default:
        break;
      }

      $this->$method($x, $y, $length, $props["color"], $widths, $side, $corner_style);
    }
  }
}
