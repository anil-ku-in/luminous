/*
 * Copyright 2010 Mark Watkinson
 * 
 * This file is part of Luminous.
 * 
 * Luminous is free software: you can redistribute it and/or
 * modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Luminous is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Luminous.  If not, see <http://www.gnu.org/licenses/>.
 */
 
 
 
 /**
   * This simply adds some extras to Luminous elements via a jQUery
   * plugin. The extras are currently a toggleable line-highlighting
   * on click
   */
 
(function($) {
    "use strict";
    
    if (typeof $ === 'undefined') { return; }
    
    /****************************************************************
     * UTILITY FUNCTIONS *
     ****************************************************************/
    
    // determines if the given element is a line element of luminous
    function isLine($line) {
        return $line.is('pre > span') && $line.parents('.luminous').length > 0;
    }
    
    function highlightLine($line) {
        $line.toggleClass('highlight');
    }
    
    function highlightLineByIndex($luminous, index) {
        var $line = $luminous.find('pre > span').eq(index);
        highlightLine($line);
    }
    
    function highlightLineByNumber($luminous, number) {
        // the line's index must take into account the initial line number
        var offset = parseInt($luminous.find('>pre').data('startline'), 10);
        if (isNaN(offset)) offset = 0;
        highlightLineByIndex($luminous, number - offset);
    }
    
    function toggleHighlightAndPlain($luminous, forceState) {
        var data = $luminous.data('luminous'),
            state = data.code.active,
            $elem = $luminous.find('>pre'),
            toSetCode, toSetState;
        
        if (forceState === 'plain') state = 'highlighted';
        else if (forceState === 'highlighted') state = 'plain';
        
        toSetCode = (state === 'plain')? data.code.highlighted : data.code.plain;
        toSetState = (state === 'plain')? 'highlighted' : 'plain';
        
        $elem.html(toSetCode);
    }
    
    
    function toggleLineNumbers($luminous, forceState) {
        var className = 'line-no-hidden', 
            $element = $luminous.find('>.code'),
            hasNumbers = $element.hasClass(className),
            show = !hasNumbers;

        if (forceState === true || forceState === false) show = forceState;
        if (show) {
            $element.removeClass(className);
        } else {
            $element.addClass(className);
        }
    }
    
    // binds the event handlers to a luminous element
    function bindLuminousExtras($element) {
        var highlightLinesData, highlightLines, data = {};
        if (!$element.is('.luminous')) { return false; }
        else if ($element.is('.bound')) { return true; }
        
        $element.addClass('bound');
        
        $element.click(function(ev) {
            var $t = $(ev.target);
            var $lines = $t.parents().add($t).
                    filter(function() { return isLine($(this)); }),
                 $line;
            if ($lines.length > 0) {
                $line = $lines.eq(0);
                highlightLine($line);
            }
        });
        
        // highlight all the initial lines
        highlightLinesData = $element.find('>pre').data('highlightlines') || "";
        highlightLines = highlightLinesData.split(",");
        $.each(highlightLines, function(i, element) {
             var lineNo = parseInt(element, 10);
             if (!isNaN(lineNo)) {
                 highlightLineByNumber($element, lineNo);
            }
        });

        data.code = {};
        data.code.highlighted = $element.find('>pre').html();
        
        data.code.plain = '';
        $element.find('>pre>span').each(function(i, e) {
            var line = $(e).text();
            line = line
                    .replace(/&/g, '&amp')
                    .replace(/>/g, '&gt;')
                    .replace(/</g, '&lt;');
        
            data.code.plain += '<span>' + line + '</span>';
        });
        data.code.active = 'highlighted';
        
        $element.data('luminous', data);
        
    }
    
    
    
    /****************************************************************
     * JQUERY PLUGIN *
     ***************************************************************/


    $.fn.luminous = function(optionsOrCommand /* variadic */) {
    
        var args = Array.prototype.slice.call(arguments);
        
        return $(this).each(function() {
            var $luminous = $(this);
            
            // no instructions - bind everything 
            if (!optionsOrCommand) {
                bindLuminousExtras($luminous);
                return;
            }
            
            // $('.luminous').luminous('highlightLine', [2, 3]);
            if (optionsOrCommand === 'highlightLine') {
                var lineNumbers = args[1];
                if (!$.isArray(lineNumbers)) 
                    lineNumbers = [lineNumbers];
                
                $.each(lineNumbers, function(index, el) {
                    highlightLineByNumber($luminous, el);
                });
                
                return;
            }
            else if (optionsOrCommand === 'show') {
                // args[1] should be 'highlighted' or 'plain'
                toggleHighlightAndPlain($luminous, args[1]);
            }
            else if (optionsOrCommand === 'showLineNumbers') {
                toggleLineNumbers($luminous, args[1]);
            }
            
        });
    };

    $(document).ready(function() {
        $('.luminous').luminous();
    });
  
}(jQuery));