<?php
/**
 * Barcode Generator (Code 128) - SVG Version
 * Generates an SVG image of a Code 128 barcode for a given text.
 * SVG is used for better compatibility as it doesn't require the GD extension.
 * Usage: barcode.php?code=TEXT_TO_ENCODE
 */

class Barcode128SVG {
    private $code128_patterns = [
        '11011001100', '11001101100', '11001100110', '10010011000', '10010001100', '10001001100', '10011001000', '10011000100', '10001100100', '11001001000',
        '11001000100', '11000100100', '10110011100', '10011011100', '10011001110', '10111001100', '10011101100', '10011100110', '11001110010', '11001011100',
        '11001001110', '11011100100', '11001110100', '11101101110', '11101001100', '11100101100', '11100100110', '11101100100', '11100110100', '11100110010',
        '11011011000', '11011000110', '11000110110', '10100011000', '10001011000', '10001000110', '10110001000', '10001101000', '10001100010', '11010001000',
        '11000101000', '11000100010', '10110111000', '10110001110', '10001101110', '10111011000', '10111000110', '10001110110', '11101110110', '11010001110',
        '11000101110', '11011101000', '11011100010', '11011101110', '11101011000', '11101000110', '11100010110', '11101101000', '11101100010', '11100011010',
        '11101111010', '11001000010', '11110001010', '10100110000', '10100001100', '10010110000', '10010000110', '10000101100', '10000100110', '10110010000',
        '10110000100', '10011010000', '10011000010', '10000110100', '10000110010', '11000010010', '11001010000', '11110111010', '11000010100', '10001111010',
        '10100111100', '10010111100', '10010011110', '10111100100', '10011110100', '10011110010', '11110100100', '11110010100', '11110010010', '11011011110',
        '11011110110', '11110110110', '10101111000', '10100011110', '10001011110', '10111101000', '10111100010', '11110101000', '11110100010', '10111011110',
        '10111101110', '11101011110', '11110101110'
    ];

    private $start_pattern = '11010000100'; // Start B pattern
    private $stop_pattern = '1100011101011'; // Stop pattern

    public function encodeText($text) {
        $text = (string)$text;
        
        // Validate input - Code 128 supports ASCII 32-126
        for ($i = 0; $i < strlen($text); $i++) {
            $ord = ord($text[$i]);
            if ($ord < 32 || $ord > 126) {
                // Replace invalid characters with space
                $text[$i] = ' ';
            }
        }
        
        // Start with Start B pattern
        $encoded = $this->start_pattern;
        $checksum = 104; // Start code value for Code B
        
        // Encode each character
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            $ord = ord($char);
            
            // Code 128B uses characters 32-126
            // Index = ASCII - 32
            $index = $ord - 32;
            
            if ($index >= 0 && $index < count($this->code128_patterns)) {
                $encoded .= $this->code128_patterns[$index];
                $checksum += ($index * ($i + 1));
            }
        }
        
        // Calculate and add checksum
        $check_index = $checksum % 103;
        $encoded .= $this->code128_patterns[$check_index];
        
        // Add stop pattern
        $encoded .= $this->stop_pattern;
        
        return $encoded;
    }

    public function generateSVG($text, $height = 120, $barWidth = 2) {
        $encoding = $this->encodeText($text);
        
        // Add quiet zones (10X on each side, where X is the narrowest bar width)
        $quietZoneWidth = 10 * $barWidth;
        $totalWidth = strlen($encoding) * $barWidth + (2 * $quietZoneWidth);
        $totalHeight = $height;
        
        $svg = '<?xml version="1.0" standalone="no"?>' . "\n";
        $svg .= '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' . "\n";
        $svg .= '<svg width="' . $totalWidth . '" height="' . $totalHeight . '" viewBox="0 0 ' . $totalWidth . ' ' . $totalHeight . '" version="1.1" xmlns="http://www.w3.org/2000/svg">' . "\n";
        $svg .= '  <rect width="100%" height="100%" fill="white" />' . "\n";
        
        // Draw barcode bars (starting after quiet zone)
        $x = $quietZoneWidth;
        for ($i = 0; $i < strlen($encoding); $i++) {
            if ($encoding[$i] == '1') {
                $svg .= '  <rect x="' . $x . '" y="0" width="' . $barWidth . '" height="' . $height . '" fill="black" />' . "\n";
            }
            $x += $barWidth;
        }
        
        $svg .= '</svg>';
        return $svg;
    }
}

$code = isset($_GET['code']) ? $_GET['code'] : 'BARCODE';
$gen = new Barcode128SVG();

// Set appropriate headers
header('Content-Type: image/svg+xml');
header('Cache-Control: no-cache, must-revalidate');

echo $gen->generateSVG($code);