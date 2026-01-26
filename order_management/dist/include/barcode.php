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
        '11001000100', '11000100100', '10110011100', '10011011100', '10011001110', '10111001100', '10011101100', '10011100110', '11001110100', '11001110010',
        '11011100100', '11011100010', '11011011100', '11011001110', '11001101110', '11001100111', '11011101100', '11011011110', '11011110110', '11110110110',
        '10101111000', '10100011110', '10001011110', '10111101000', '10111100010', '11110101000', '11110100010', '10111101110', '11110101110', '11110111010',
        '10110111000', '10110001110', '10001101110', '10111011000', '10111000110', '10001110110', '11101101000', '11101100010', '11100011010', '11101101100',
        '11101100110', '11100011011', '11101101100', '11101100110', '11100011010', '11011011000', '11011000110', '11000110110', '11011101100', '11011100110',
        '11000111011', '11011011110', '11110110110', '10101111000', '10100011110', '10001011110', '10111101000', '10111100010', '11110101000', '11110100010',
        '10111101110', '11110101110', '11110111010', '11110111011', '11011110110', '11011111010', '11011111011', '11101111101', '11101011110', '11101011110',
        '11110101110', '11110111010', '11000110110', '11011000110', '11000110110', '10110111100', '10110011110', '10011011110', '10111101100', '10111100110',
        '11101101111', '11111010110', '11111011010', '10111101111', '10111111011', '11101111101', '11101011111', '11101110111', '11101101111', '11110110110',
        '11110110111', '11111011011', '11011011111'
    ];

    private $start_b = '11010010000';
    private $stop = '1100011101011';

    public function generate($text) {
        $text = (string)$text;
        $encoding = $this->start_b;
        $checksum = 104; // Start B index

        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            $ord = ord($char);
            $index = $ord - 32;
            if ($index < 0 || $index > 102) $index = 0;

            $encoding .= $this->code128_patterns[$index];
            $checksum += ($index * ($i + 1));
        }

        $check_index = $checksum % 103;
        $encoding .= $this->code128_patterns[$check_index];
        $encoding .= $this->stop;

        return $encoding;
    }

    public function generateSVG($text, $height = 120, $barWidth = 3, $fontSize = 32) {
        $encoding = $this->generate($text);
        $totalWidth = strlen($encoding) * $barWidth;
        $totalHeight = $height + 50; // Extra space for larger text
        
        $svg = '<?xml version="1.0" standalone="no"?>' . "\n";
        $svg .= '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' . "\n";
        $svg .= '<svg width="' . $totalWidth . '" height="' . $totalHeight . '" viewBox="0 0 ' . $totalWidth . ' ' . $totalHeight . '" version="1.1" xmlns="http://www.w3.org/2000/svg">' . "\n";
        $svg .= '  <rect width="100%" height="100%" fill="white" />' . "\n";
        
        for ($i = 0; $i < strlen($encoding); $i++) {
            if ($encoding[$i] == '1') {
                $svg .= '  <rect x="' . ($i * $barWidth) . '" y="0" width="' . $barWidth . '" height="' . $height . '" fill="black" />' . "\n";
            }
        }
        
        // Add text below barcode with larger font size
        $svg .= '  <text x="' . ($totalWidth / 2) . '" y="' . ($height + 35) . '" font-family="Arial, sans-serif" font-size="' . $fontSize . '" font-weight="bold" text-anchor="middle" fill="black">' . htmlspecialchars($text) . '</text>' . "\n";
        
        $svg .= '</svg>';
        return $svg;
    }
}

$code = isset($_GET['code']) ? $_GET['code'] : 'BARCODE';
$gen = new Barcode128SVG();

// You can adjust the font size via URL parameter
$fontSize = isset($_GET['fontsize']) ? intval($_GET['fontsize']) : 24;

header('Content-Type: image/svg+xml');
echo $gen->generateSVG($code, 120, 3, $fontSize);