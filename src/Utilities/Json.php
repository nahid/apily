<?php
/**
 * @package Nahid\Apily

 */

namespace Nahid\Apily\Utilities;

/**
 * Services for json.
 */
class Json {

    /**
     * Indents a flat JSON string to make it more human-readable.
     *
     * @param string $json The original JSON string to process.
     *
     * @return string Indented version of the original JSON string.
     */
    public static function pretty($json) {

        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '    ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
                // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }

    public static function highlight(string $json): string
    {
        $prettyJson = self::pretty($json);
        // Define styles for JSON components
        $styles = [
            // Keys (e.g., "name":)
            '/"(.*?)":/' => '<fg=green>"$1":</>', // Green for keys
            // Strings (e.g., "John Doe")
            '/:"(.*?)"/' => '<fg=white>"$1"</>', // White for string values
            // Numbers (e.g., 30)
            '/\b(-?[0-9]*\.?[0-9]+)\b/' => '<fg=bright-magenta>$1</>', // White for numeric values
            // Booleans (e.g., true, false)
            '/\b(true|false)\b/' => '<fg=bright-magenta>$1</>', // White for boolean values
            // Null values
            '/\b(null)\b/' => '<fg=white>$1</>', // White for null values
            // Brackets and braces
            '/(\{|\}|\[|\])/' => '<fg=yellow>$1</>', // Yellow for brackets
        ];

        // Apply styles to the JSON string
        foreach ($styles as $pattern => $replacement) {
            $prettyJson = preg_replace($pattern, $replacement, $prettyJson);
        }

        return $prettyJson;
    }
}